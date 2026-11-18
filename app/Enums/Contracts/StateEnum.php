<?php

namespace App\Enums\Contracts;

use App\Enums\Transitions\TransitionTable;

/**
 * Kontrak state machine: satu Enum per state machine, satu tabel transisi (TRD.md §5.1).
 */
interface StateEnum
{
    public static function transitions(): TransitionTable;
}
