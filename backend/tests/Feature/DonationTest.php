<?php

namespace Tests\Feature;

use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Paystack is always faked here — no request ever reaches the real payment service. */
class DonationTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'sk_test_fake_key_for_tests';

    private function configure(): void
    {
        config(['services.paystack.secret_key' => self::KEY, 'services.frontend.url' => 'https://ennavizion.com']);
    }

    private function fakeVerify(string $status, int $amountKobo, string $currency = 'NGN'): void
    {
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => [
            'status' => $status, 'amount' => $amountKobo, 'currency' => $currency,
            'reference' => 'x', 'paid_at' => '2026-11-08T10:00:00Z',
        ]])]);
    }

    private function start(int $naira = 10000): Submission
    {
        Http::fake(['api.paystack.co/transaction/initialize' => fn ($req) => Http::response(['status' => true, 'data' => [
            'authorization_url' => 'https://checkout.paystack.com/abc', 'access_code' => 'abc', 'reference' => $req['reference'],
        ]])]);
        $ref = $this->postJson('/api/donations/initialize', [
            'amount' => (string) $naira, 'donor_name' => 'Ngozi', 'donor_email' => 'ngozi@example.com', 'donation_type' => 'once',
        ])->assertOk()->assertJsonPath('authorization_url', 'https://checkout.paystack.com/abc')->json('reference');

        return Submission::where('reference', $ref)->firstOrFail();
    }

    public function test_donations_are_closed_when_paystack_is_not_configured(): void
    {
        config(['services.paystack.secret_key' => null]);
        $this->getJson('/api/content')->assertJsonPath('SITE.donations', false);
        $this->postJson('/api/donations/initialize', ['amount' => '5000', 'donor_name' => 'A', 'donor_email' => 'a@example.com'])
            ->assertStatus(503);
        $this->assertSame(0, Submission::count());
    }

    public function test_initialise_records_a_pending_donation_and_sends_our_reference_and_kobo(): void
    {
        $this->configure();
        $this->getJson('/api/content')->assertJsonPath('SITE.donations', true);
        $d = $this->start(10000);
        $this->assertSame('pending', $d->status);
        $this->assertSame(1_000_000, $d->data['amount_kobo']);
        Http::assertSent(fn ($req) => str_ends_with($req->url(), '/transaction/initialize')
            && $req['reference'] === $d->reference && $req['amount'] === 1_000_000 && $req['currency'] === 'NGN'
            && $req->hasHeader('Authorization', 'Bearer '.self::KEY));
    }

    public function test_amount_limits(): void
    {
        $this->configure();
        $this->postJson('/api/donations/initialize', ['amount' => 'custom', 'custom_amount' => 100, 'donor_name' => 'A', 'donor_email' => 'a@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
    }

    public function test_verified_matching_payment_is_marked_paid(): void
    {
        $this->configure();
        $d = $this->start(10000);
        $this->fakeVerify('success', 1_000_000);
        $this->getJson("/api/donations/verify/{$d->reference}")->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame('paid', $d->fresh()->status);
    }

    public function test_amount_mismatch_is_not_marked_paid(): void
    {
        $this->configure();
        $d = $this->start(10000);
        $this->fakeVerify('success', 50_000); // ₦500, not ₦10,000
        $this->getJson("/api/donations/verify/{$d->reference}")->assertJsonPath('status', 'pending');
        $this->assertSame('review', $d->fresh()->status);
    }

    public function test_abandoned_payment_is_failed_and_paid_is_never_downgraded(): void
    {
        $this->configure();
        $d = $this->start(10000);
        $tx = fn ($status) => ['status' => true, 'data' => ['status' => $status, 'amount' => 1_000_000, 'currency' => 'NGN', 'reference' => $d->reference]];
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::sequence()
            ->push($tx('abandoned'))->push($tx('success'))->push($tx('failed'))]);

        $this->getJson("/api/donations/verify/{$d->reference}")->assertJsonPath('status', 'failed');
        $this->assertSame('failed', $d->fresh()->status);

        $this->getJson("/api/donations/verify/{$d->reference}")->assertJsonPath('status', 'success');

        // Once paid, Paystack isn't asked again and nothing can undo it.
        $this->getJson("/api/donations/verify/{$d->reference}")->assertJsonPath('status', 'success');
        $this->assertSame('paid', $d->fresh()->status);
    }

    public function test_unknown_reference_is_not_sent_to_paystack(): void
    {
        $this->configure();
        Http::fake();
        $this->getJson('/api/donations/verify/DON-2026-NOPE99')->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_webhook_requires_a_valid_signature(): void
    {
        $this->configure();
        $d = $this->start(10000);
        $body = json_encode(['event' => 'charge.success', 'data' => ['status' => 'success', 'amount' => 1_000_000, 'currency' => 'NGN', 'reference' => $d->reference]]);

        $this->call('POST', '/api/donations/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => 'forged'], $body)
            ->assertStatus(401);
        $this->assertSame('pending', $d->fresh()->status);

        $sig = hash_hmac('sha512', $body, self::KEY);
        foreach ([1, 2] as $_) { // repeated delivery is harmless
            $this->call('POST', '/api/donations/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => $sig], $body)
                ->assertOk();
        }
        $this->assertSame('paid', $d->fresh()->status);
        $this->assertSame('webhook', $d->fresh()->data['checked_via']);
    }
}
