<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Receives the public site's contact, enrolment and newsletter forms (donations have their
 * own flow in DonationController). Each lands as a "new" Submission, viewable in the
 * Filament admin under Submissions. Only the fields listed per type are stored — anything
 * else a client sends is dropped rather than written into the JSON column.
 */
class SubmissionController extends Controller
{
    private const RULES = [
        'contact' => [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'topic' => ['required', 'string', 'max:50'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ],
        'enrolment' => [
            'player_first' => ['required', 'string', 'max:100'],
            'player_last' => ['required', 'string', 'max:100'],
            'dob' => ['required', 'date', 'before:today'],
            'position' => ['required', 'string', 'max:50'],
            'current_club' => ['nullable', 'string', 'max:255'],
            'program' => ['required', 'string', 'max:100'],
            'centre' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_phone' => ['required', 'string', 'regex:/^[+0-9 ()-]{7,}$/'],
            'guardian_email' => ['required', 'email', 'max:255'],
            'consent' => ['required', 'accepted'],
        ],
        'newsletter' => [
            'email' => ['required', 'email', 'max:255'],
        ],
    ];

    public function store(Request $request)
    {
        $type = $request->validate([
            'type' => ['required', Rule::in(array_keys(self::RULES))],
        ])['type'];

        $data = $request->validate(self::RULES[$type]);

        // Re-subscribing shouldn't pile up duplicate newsletter rows.
        if ($type === 'newsletter' && Submission::where('type', 'newsletter')
            ->whereJsonContains('data->email', $data['email'])->exists()) {
            return response()->json(['ok' => true], 201);
        }

        Submission::create(['type' => $type, 'data' => $data, 'status' => 'new']);

        return response()->json(['ok' => true], 201);
    }
}
