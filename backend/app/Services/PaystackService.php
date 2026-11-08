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

    public function isConfigured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    public function isTestMode(): bool
    {
        return str_starts_with((string) config('services.paystack.secret_key'), 'sk_test_');
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

    /** Paystack signs each webhook body with HMAC-SHA512 using the secret key. */
    public function validSignature(string $payload, ?string $signature): bool
    {
        return $this->isConfigured() && filled($signature)
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
