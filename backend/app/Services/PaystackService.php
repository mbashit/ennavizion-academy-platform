<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Paystack's REST API. Every call here happens server-side using the
 * secret key from config('services.paystack.secret_key') (never sent to the browser) — the
 * frontend only ever receives an authorization_url to redirect to, or a verified result.
 *
 * Uses Laravel's Http facade directly rather than pulling in a Paystack package: the whole
 * integration only needs two endpoints (initialize + verify), so a small typed wrapper is
 * easier to audit than an extra dependency.
 */
class PaystackService
{
    private const BASE_URL = 'https://api.paystack.co';

    public function isConfigured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    /**
     * Starts a transaction. $amountNaira is whole naira (not kobo) — Paystack's API wants
     * the amount in kobo, so the *100 conversion happens in here, in one place.
     *
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initialize(string $email, float $amountNaira, string $callbackUrl, array $metadata = []): array
    {
        $data = $this->request('post', '/transaction/initialize', [
            'email' => $email,
            'amount' => (int) round($amountNaira * 100),
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ]);

        return [
            'authorization_url' => $data['authorization_url'],
            'access_code' => $data['access_code'],
            'reference' => $data['reference'],
        ];
    }

    /**
     * Verifies a transaction by reference. This is the ONLY source of truth for whether a
     * donation was actually paid — never trust a client-side "success" redirect alone.
     *
     * @return array{status: string, amountNaira: float, reference: string, paidAt: ?string}
     */
    public function verify(string $reference): array
    {
        $data = $this->request('get', '/transaction/verify/'.rawurlencode($reference));

        return [
            'status' => $data['status'], // 'success' | 'failed' | 'abandoned' | ...
            'amountNaira' => $data['amount'] / 100,
            'reference' => $data['reference'],
            'paidAt' => $data['paid_at'] ?? null,
        ];
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Online payment is not connected yet. Set PAYSTACK_SECRET_KEY in the backend .env file.'
            );
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->acceptJson()
            ->{$method}(self::BASE_URL.$path, $payload);

        if (! $response->successful() || $response->json('status') !== true) {
            throw new RuntimeException($response->json('message') ?: 'Paystack request failed.');
        }

        return $response->json('data');
    }
}
