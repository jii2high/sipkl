<?php

namespace App\Support;

class SkalaPredikat
{
    /** BR28: K (<70), C (70–80), B (81–90), SB (91–100). */
    public static function dari(float $nilai): string
    {
        return match (true) {
            $nilai < 70 => 'K',
            $nilai <= 80 => 'C',
            $nilai <= 90 => 'B',
            default => 'SB',
        };
    }
}
