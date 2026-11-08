<?php

namespace App\Support;

/** Reads an age band out of a programme label: "Ages 6–9" → [6, 9]; "Under 12" → [12, 12]. */
final class AgeRange
{
    /** @return array{0:int,1:int}|null */
    public static function parse(?string $label): ?array
    {
        if (! preg_match_all('/\d+/', (string) $label, $m) || empty($m[0])) {
            return null;
        }

        return [(int) $m[0][0], (int) end($m[0])];
    }
}
