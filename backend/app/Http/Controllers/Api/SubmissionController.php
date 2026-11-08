<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Program;
use App\Models\Submission;
use App\Support\AgeRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Receives the public site's trial application ("enrolment"), contact and newsletter forms.
 * Donations have their own flow in DonationController.
 *
 * Every submission is validated here regardless of what the browser checked, only the
 * listed fields are stored, medical notes go to the encrypted `sensitive` column, and the
 * staff notification email carries a reference number — never a child's details.
 */
class SubmissionController extends Controller
{
    private const NAME = ['required', 'string', 'max:100', 'not_regex:/[<>]/'];

    private function rules(string $type): array
    {
        return match ($type) {
            'contact' => [
                'name' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50', 'regex:/^[+0-9 ()\-]*$/'],
                'topic' => ['required', 'string', Rule::in(['enrolment', 'visit', 'league', 'scout', 'community', 'sponsor', 'partner', 'services', 'safeguarding', 'other'])],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
            ],
            'enrolment' => array_filter([
                'player_first' => self::NAME,
                'player_last' => self::NAME,
                'dob' => ['required', 'date_format:Y-m-d', 'before:today'],
                'position' => ['required', Rule::in(['Goalkeeper', 'Defender', 'Midfielder', 'Forward', 'Not sure yet'])],
                'current_club' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
                'program' => ['required', 'string', Rule::in(Program::pluck('name')->all())],
                // Only asked for once the academy has published training locations.
                'centre' => Center::exists()
                    ? ['required', 'string', Rule::in(Center::pluck('name')->all())]
                    : ['nullable', 'string', 'max:255'],
                'medical_notes' => ['nullable', 'string', 'max:2000'],
                'notes' => ['nullable', 'string', 'max:2000'], // older cached copies of the form
                'guardian_name' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
                'guardian_phone' => ['required', 'string', 'regex:/^[+0-9 ()\-]{7,30}$/'],
                'guardian_email' => ['required', 'email', 'max:255'],
                'consent' => ['required', 'accepted'],
            ]),
            'newsletter' => [
                'email' => ['required', 'email', 'max:255'],
            ],
        };
    }

    public function store(Request $request): JsonResponse
    {
        $type = $request->validate([
            'type' => ['required', Rule::in(['contact', 'enrolment', 'newsletter'])],
        ])['type'];

        // Honeypot: a hidden field people never see. Bots that fill it get an ordinary-looking
        // response and nothing is stored or emailed.
        if (filled($request->input('website'))) {
            Log::info('Submission dropped by spam trap', ['type' => $type, 'ip' => $request->ip()]);

            return response()->json(['ok' => true], 201);
        }

        $data = collect($request->validate($this->rules($type)))
            ->map(fn ($v) => is_string($v) ? trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v)) : $v)
            ->all();

        $sensitive = null;
        $fingerprint = null;

        if ($type === 'enrolment') {
            $this->checkAge($data);
            $notes = trim(($data['medical_notes'] ?? '')."\n".($data['notes'] ?? ''));
            unset($data['medical_notes'], $data['notes']);
            $sensitive = $notes !== '' ? ['medical_notes' => $notes] : null;
            $data['consent'] = true;
            $data['consent_at'] = now()->toIso8601String();

            // Same player, same guardian, within a day: treat as the same application (a
            // double-click or a retry after a slow network), not a new one.
            $fingerprint = hash('sha256', mb_strtolower($data['player_first'].'|'.$data['player_last'].'|'.$data['dob'].'|'.$data['guardian_email']));
            $existing = Submission::where('type', 'enrolment')->where('fingerprint', $fingerprint)
                ->where('created_at', '>=', now()->subDay())->first();
            if ($existing) {
                return response()->json(['ok' => true, 'reference' => $existing->reference, 'duplicate' => true], 200);
            }
        }

        if ($type === 'newsletter' && Submission::where('type', 'newsletter')
            ->whereJsonContains('data->email', $data['email'])->exists()) {
            return response()->json(['ok' => true], 201);
        }

        $submission = Submission::create([
            'type' => $type, 'data' => $data, 'sensitive' => $sensitive,
            'fingerprint' => $fingerprint, 'status' => 'new',
        ]);

        Log::info('Submission received', ['type' => $type, 'id' => $submission->id]);
        $this->notify($submission);

        return response()->json(['ok' => true, 'reference' => $type === 'newsletter' ? null : $submission->reference], 201);
    }

    /**
     * The academy takes players within its programmes' combined age span, and each programme's
     * own band applies to the programme chosen. Age is measured on the day of application.
     */
    private function checkAge(array $data): void
    {
        $age = Carbon::parse($data['dob'])->age;
        $bands = Program::pluck('ages', 'name')->map(fn ($l) => AgeRange::parse($l))->filter();

        if ($bands->isNotEmpty()) {
            [$min, $max] = [$bands->min(fn ($b) => $b[0]), $bands->max(fn ($b) => $b[1])];
            if ($age < $min || $age > $max) {
                throw ValidationException::withMessages(['dob' => "The academy's programmes are for players aged {$min} to {$max}. This player is {$age}."]);
            }
        }

        $band = $bands->get($data['program']);
        if ($band && ($age < $band[0] || $age > $band[1])) {
            throw ValidationException::withMessages(['program' => "{$data['program']} is for ages {$band[0]}–{$band[1]}. Choose the programme that matches the player's age ({$age})."]);
        }
    }

    /**
     * Tells staff something arrived. Deliberately contains no personal details — staff open
     * the record in the admin panel, behind a login. The row is already saved, so a mail
     * failure is logged rather than shown to the visitor.
     */
    private function notify(Submission $submission): void
    {
        $to = config('services.notify.email');
        if (blank($to) || $submission->type === 'newsletter') {
            return;
        }

        $label = ['enrolment' => 'trial application', 'contact' => 'contact message'][$submission->type] ?? $submission->type;

        try {
            Mail::raw(
                "A new {$label} was received through the website.\n\n".
                "Reference: {$submission->reference}\n\n".
                'Sign in to the admin panel to view it: '.url('/admin/submissions'),
                fn ($m) => $m->to($to)->subject("New {$label} {$submission->reference}"),
            );
        } catch (\Throwable $e) {
            Log::warning('Submission notification email failed', ['submission' => $submission->id, 'error' => $e->getMessage()]);
        }
    }
}
