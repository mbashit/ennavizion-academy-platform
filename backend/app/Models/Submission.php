<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A form sent from the public site: enrolment (trial application), contact, newsletter or
 * donation. Only signed-in admin users can see these (Filament panel).
 *
 * - `data` holds the ordinary fields.
 * - `sensitive` holds medical/injury notes, encrypted with the app key. It is never included
 *   in notification emails, the admin list view or the public API.
 * - `reference` is generated on create (or set explicitly — donations use it as the Paystack
 *   transaction reference).
 */
class Submission extends Model
{
    public const PREFIX = ['enrolment' => 'ENR', 'contact' => 'MSG', 'donation' => 'DON', 'newsletter' => 'NEW'];

    protected $fillable = ['type', 'reference', 'data', 'sensitive', 'fingerprint', 'status'];

    protected $hidden = ['sensitive', 'fingerprint'];

    protected $casts = [
        'data' => 'array',
        'sensitive' => 'encrypted:array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Submission $s) {
            $s->reference ??= static::newReference($s->type);
        });
    }

    /** e.g. ENR-2026-7KQ4XM — unambiguous characters only, so it can be read out on the phone. */
    public static function newReference(string $type): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $ref = sprintf('%s-%s-%s', self::PREFIX[$type] ?? 'SUB', now()->format('Y'), $code);
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    /** Short, non-identifying label for lists: never shows a child's details. */
    public function summary(): string
    {
        $d = $this->data ?? [];

        return match ($this->type) {
            'enrolment' => trim(($d['program'] ?? 'Application').' — guardian: '.($d['guardian_name'] ?? '')),
            'donation' => trim(($d['donor_name'] ?? '').' — ₦'.number_format((float) ($d['amount'] ?? 0))),
            'contact' => trim(($d['name'] ?? '').' — '.($d['topic'] ?? '')),
            default => (string) ($d['email'] ?? ''),
        };
    }
}
