<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DonationController extends Controller
{
    public function __construct(private readonly PaystackService $paystack)
    {
    }

    /**
     * Starts a one-time donation. Creates a "pending" Submission first (so we have a record
     * even if the visitor abandons checkout), then asks Paystack for a hosted checkout URL.
     */
    public function initialize(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'string'],
            'custom_amount' => ['nullable', 'numeric', 'min:500'],
            'donor_name' => ['required', 'string', 'max:255'],
            'donor_email' => ['required', 'email'],
            'message' => ['nullable', 'string', 'max:2000'],
            'donation_type' => ['nullable', 'in:once'], // 'monthly' isn't built yet — see donate.html
        ]);

        $amount = $validated['amount'] === 'custom'
            ? (float) ($validated['custom_amount'] ?? 0)
            : (float) $validated['amount'];

        if ($amount < 500) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount of at least ₦500.']);
        }

        $submission = Submission::create([
            'type' => 'donation',
            'status' => 'pending',
            'data' => [
                'donor_name' => $validated['donor_name'],
                'donor_email' => $validated['donor_email'],
                'message' => $validated['message'] ?? null,
                'amount' => $amount,
                'donation_type' => 'once',
            ],
        ]);

        try {
            $result = $this->paystack->initialize(
                email: $validated['donor_email'],
                amountNaira: $amount,
                callbackUrl: rtrim(config('services.frontend.url'), '/').'/donate.html',
                metadata: ['submission_id' => $submission->id],
            );
        } catch (\Throwable $e) {
            $submission->update(['status' => 'failed']);

            return response()->json(['message' => $e->getMessage()], 503);
        }

        $submission->update(['data' => [...$submission->data, 'paystack_reference' => $result['reference']]]);

        return response()->json(['authorization_url' => $result['authorization_url']]);
    }

    /**
     * Called by the frontend after Paystack redirects the donor back with ?reference=xxx.
     * This is the only place a donation is ever marked paid — always re-checked against
     * Paystack itself, never trusted from the redirect alone.
     */
    public function verify(string $reference)
    {
        $submission = Submission::where('type', 'donation')
            ->whereJsonContains('data->paystack_reference', $reference)
            ->first();

        try {
            $result = $this->paystack->verify($reference);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 503);
        }

        $paid = $result['status'] === 'success';

        if ($submission) {
            $submission->update(['status' => $paid ? 'actioned' : 'failed']);
        }

        return response()->json([
            'status' => $paid ? 'success' : 'failed',
            'amount' => $result['amountNaira'],
            'donor_name' => $submission?->data['donor_name'],
        ]);
    }
}
