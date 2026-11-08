<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Paystack's REST API. Every call happens server-side with the secret
 * key from config('services.paystack.secret_key'), which is never sent to the browser.
 * Use a test key (sk_test_...) until the academy authorises live payments.
 */
class PaystackService
{
    private const BASE_URL = 'https://api.paystack.co';

    /**
     * The activation state (business plan section 19):
     *   off  — not configured, or mode and key don't agree: contribution information is shown,
     *          checkout is unavailable;
     *   test — PAYSTACK_MODE=test with an sk_test_ key: checkout works, clearly labelled, no
     *          real money, never counted as a real contribution;
     *   live — PAYSTACK_MODE=live with an sk_live_ key, set deliberately once authorised.
     */
    public function mode(): string
    {
        $mode = strtolower(trim((string) config('services.paystack.mode', 'off')));
        $key = (string) config('services.paystack.secret_key');

        return match (true) {
            $mode === 'test' && str_starts_with($key, 'sk_test_') => 'test',
            $mode === 'live' && str_starts_with($key, 'sk_live_') => 'live',
            default => 'off',
        };
    }

    public function isConfigured(): bool
    {
        return $this->mode() !== 'off';
    }

    public function isTestMode(): bool
    {
        return $this->mode() === 'test';
    }

    /**
     * Starts a transaction under OUR reference, so the record exists before the donor leaves
     * the site and every later check can be matched to it. Amount is in kobo.
     *
     * @return array{authorization_url: string, reference: string}
     */
    public function initialize(string $email, int $amountKobo, string $reference, string $callbackUrl, array $metadata = []): array
    {
        $data = $this->request('post', '/transaction/initialize', [
            'email' => $email,
            'amount' => $amountKobo,
            'currency' => 'NGN',
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ]);

        return ['authorization_url' => $data['authorization_url'], 'reference' => $data['reference']];
    }

    /**
     * Asks Paystack for the transaction's real state. The only source of truth for whether a
     * donation was paid — a browser redirect alone proves nothing.
     *
     * @return array{status: string, amount: int, currency: string, reference: string, paid_at: ?string}
     */
    public function verify(string $reference): array
    {
        return self::normalise($this->request('get', '/transaction/verify/'.rawurlencode($reference)));
    }

    /** Same shape for API responses and webhook payloads. */
    public static function normalise(array $data): array
    {
        return [
            'status' => (string) ($data['status'] ?? ''),  // success | failed | abandoned | ongoing | pending ...
            'amount' => (int) ($data['amount'] ?? 0),      // kobo
            'currency' => (string) ($data['currency'] ?? ''),
            'reference' => (string) ($data['reference'] ?? ''),
            'paid_at' => $data['paid_at'] ?? $data['paidAt'] ?? null,
        ];
    }

    /**
     * Paystack signs each webhook body with HMAC-SHA512 using the secret key. Checked whenever a
     * key exists, even if checkout has since been switched off, so a payment completed just
     * before that is still recorded.
     */
    public function validSignature(string $payload, ?string $signature): bool
    {
        return filled(config('services.paystack.secret_key')) && filled($signature)
            && hash_equals(hash_hmac('sha512', $payload, (string) config('services.paystack.secret_key')), $signature);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Online donations are not open yet.');
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->acceptJson()->timeout(15)
            ->{$method}(self::BASE_URL.$path, $payload);

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException($response->json('message') ?: 'The payment service did not respond.');
        }

        return $response->json('data');
    }
}
