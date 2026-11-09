<?php

namespace App\Models;

use App\Notifications\TrialApplicationReceived;
use App\Notifications\TrialApplicationUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * A parent's trial application for one of their players. Staff move it through the statuses
 * in the admin panel; the parent sees the status, trial details and message_to_parent in the
 * portal and is emailed when any of those change. staff_notes stay internal.
 */
class TrialApplication extends Model
{
    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'trial_scheduled' => 'Trial scheduled',
        'accepted' => 'Place offered',
        'waitlisted' => 'Waiting list',
        'declined' => 'Not offered a place',
        'withdrawn' => 'Withdrawn',
    ];

    /** Statuses after which the parent can no longer withdraw. */
    public const CLOSED = ['accepted', 'declined', 'withdrawn'];

    protected $fillable = [
        'player_id', 'program_id', 'program_name', 'centre', 'parent_note',
        'status', 'trial_at', 'trial_location', 'message_to_parent', 'staff_notes', 'consent_at',
    ];

    protected $casts = [
        'trial_at' => 'datetime',
        'consent_at' => 'datetime',
    ];

    protected $hidden = ['staff_notes'];

    protected static function booted(): void
    {
        static::creating(function (TrialApplication $a) {
            $a->reference ??= self::newReference();
            $a->status ??= 'submitted';
        });

        static::created(function (TrialApplication $a) {
            $a->parent?->notify(new TrialApplicationReceived($a));
            self::notifyStaff($a);
        });

        // Tell the parent when something they can see changes — never for staff_notes alone.
        static::updated(function (TrialApplication $a) {
            if ($a->wasChanged(['status', 'trial_at', 'trial_location', 'message_to_parent']) && $a->status !== 'withdrawn') {
                $a->parent?->notify(new TrialApplicationUpdated($a));
            }
        });
    }

    /** e.g. ENR-2026-7KQ4XM — the same format as website applications. */
    public static function newReference(): string
    {
        do {
            $ref = Submission::newReference('enrolment');
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    /** Staff alert: reference only, no child's details (same rule as website submissions). */
    private static function notifyStaff(TrialApplication $a): void
    {
        $to = config('services.notify.email');
        if (blank($to)) {
            return;
        }
        try {
            Mail::raw(
                "A new trial application was made in the parent portal.\n\nReference: {$a->reference}\n\n".
                'Sign in to the admin panel to review it: '.url('/admin/trial-applications'),
                fn ($m) => $m->to($to)->subject("New trial application {$a->reference}"),
            );
        } catch (\Throwable $e) {
            Log::warning('Trial application staff email failed', ['application' => $a->id, 'error' => $e->getMessage()]);
        }
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, self::CLOSED, true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
