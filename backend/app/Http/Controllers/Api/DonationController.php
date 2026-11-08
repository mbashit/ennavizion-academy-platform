<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * One-time donations through Paystack's hosted checkout.
 *
 * Status of a donation Submission: pending → paid | failed | review.
 *   - Only Paystack's own answer (verify API or a signed webhook) can make it "paid", and only
 *     when the amount and currency match what this server asked for.
 *   - "paid" is final: a later failed/abandoned answer never overwrites it, and a repeated
 *     webhook or page reload doesn't process it twice.
 *   - "review" means Paystack reported success but something didn't match; staff check it.
 */
class DonationController extends Controller
{
    private const MIN_NAIRA = 500;
    private const MAX_NAIRA = 10_000_000;

    public function __construct(private readonly PaystackService $paystack)
    {
    }

    public function initialize(Request $request): JsonResponse
    {
        if (! $this->paystack->isConfigured()) {
            return response()->json(['message' => 'Online donations are not open yet.'], 503);
        }
        if (filled($request->input('website'))) { // spam trap
            return response()->json(['message' => 'Checkout could not be started.'], 422);
        }

        $v = $request->validate([
            'amount' => ['required', 'string', 'max:20'],
            'custom_amount' => ['nullable', 'numeric'],
            'donor_name' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'donor_email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'donation_type' => ['nullable', 'in:once'],
        ]);

        $naira = $v['amount'] === 'custom' ? (float) ($v['custom_amount'] ?? 0) : (float) $v['amount'];
        if ($naira < self::MIN_NAIRA || $naira > self::MAX_NAIRA) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount between ₦500 and ₦10,000,000.']);
        }
        $kobo = (int) round($naira * 100);

        $donation = Submission::create([
            'type' => 'donation',
            'status' => 'pending',
            'data' => [
                'donor_name' => trim($v['donor_name']),
                'donor_email' => $v['donor_email'],
                'message' => $v['message'] ?? null,
                'amount' => $naira,
                'amount_kobo' => $kobo,
                'currency' => 'NGN',
                'mode' => $this->paystack->isTestMode() ? 'test' : 'live',
            ],
        ]);

        try {
            $result = $this->paystack->initialize(
                email: $v['donor_email'],
                amountKobo: $kobo,
                reference: $donation->reference,
                callbackUrl: rtrim(config('services.frontend.url'), '/').'/donate.html',
                metadata: ['submission_id' => $donation->id],
            );
        } catch (\Throwable $e) {
            $donation->update(['status' => 'failed']);
            Log::warning('Paystack initialise failed', ['submission' => $donation->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Checkout could not be started. No payment was taken.'], 503);
        }

        return response()->json(['authorization_url' => $result['authorization_url'], 'reference' => $donation->reference]);
    }

    /** Called by donate.html when Paystack sends the donor back with ?reference=… */
    public function verify(string $reference): JsonResponse
    {
        $donation = Submission::where('type', 'donation')->where('reference', $reference)->first();
        if (! $donation) {
            return response()->json(['status' => 'failed', 'message' => "We couldn't find that donation."], 404);
        }

        if ($donation->status !== 'paid') {
            try {
                $this->apply($donation, $this->paystack->verify($reference), 'verify');
            } catch (\Throwable $e) {
                Log::warning('Paystack verify failed', ['submission' => $donation->id, 'error' => $e->getMessage()]);

                return response()->json(['status' => 'pending', 'message' => "We couldn't reach the payment service to confirm this yet."], 503);
            }
            $donation->refresh();
        }

        return response()->json(match ($donation->status) {
            'paid' => ['status' => 'success', 'amount' => $donation->data['amount'], 'donor_name' => $donation->data['donor_name'] ?? null],
            'pending' => ['status' => 'pending'],
            'review' => ['status' => 'pending', 'message' => 'Your payment is being checked by the academy.'],
            default => ['status' => 'failed', 'message' => "This payment wasn't completed."],
        });
    }

    /** Paystack → us. Signed with the secret key; anything unsigned is rejected. */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        if (! $this->paystack->validSignature($payload, $request->header('x-paystack-signature'))) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $event = json_decode($payload, true) ?: [];
        if (($event['event'] ?? '') === 'charge.success') {
            $tx = PaystackService::normalise($event['data'] ?? []);
            $donation = Submission::where('type', 'donation')->where('reference', $tx['reference'])->first();
            if ($donation) {
                $this->apply($donation, $tx, 'webhook');
            } else {
                Log::info('Paystack webhook for unknown reference', ['reference' => $tx['reference']]);
            }
        }

        return response()->json(['ok' => true]); // always 200 for a valid signature, or Paystack retries
    }

    /** Records Paystack's answer on the donation. Idempotent; never downgrades "paid". */
    private function apply(Submission $donation, array $tx, string $source): void
    {
        DB::transaction(function () use ($donation, $tx, $source) {
            $d = Submission::whereKey($donation->id)->lockForUpdate()->first();
            if ($d->status === 'paid') {
                return;
            }

            $data = $d->data;
            $data['paystack_status'] = $tx['status'];
            $data['checked_via'] = $source;

            if ($tx['status'] === 'success') {
                $matches = $tx['amount'] === (int) ($data['amount_kobo'] ?? round(($data['amount'] ?? 0) * 100))
                    && strtoupper($tx['currency']) === 'NGN';
                $data['paid_at'] = $tx['paid_at'];
                $status = $matches ? 'paid' : 'review';
                if (! $matches) {
                    Log::warning('Paystack amount/currency mismatch', ['submission' => $d->id, 'got' => $tx['amount'].' '.$tx['currency']]);
                }
            } elseif (in_array($tx['status'], ['failed', 'abandoned', 'reversed'], true)) {
                $status = 'failed';
            } else {
                $status = 'pending'; // ongoing / processing / queued
            }

            $d->update(['data' => $data, 'status' => $status]);
        });
    }
}
