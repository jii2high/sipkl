<?php

namespace App\Enums;

use App\Enums\Concerns\HasTransitions;
use App\Enums\Contracts\StateEnum;
use App\Enums\Transitions\TransitionTable;
use App\Support\StatusChip;

/**
 * State machine `bimbingan_submissions.status` (F6, PRD F6). Satu baris bimbingan
 * berakhir `revisi` atau `diterima`; unggahan ulang selalu baris baru.
 */
enum BimbinganStatus: string implements StateEnum
{
    use HasTransitions;

    case MenungguFeedback = 'menunggu_feedback';
    case Revisi = 'revisi';
    case Diterima = 'diterima';

    public function label(): string
    {
        return StatusChip::label($this->value);
    }

    public static function transitions(): TransitionTable
    {
        return new TransitionTable([
            self::MenungguFeedback->value => [
                self::Revisi->value => [Role::GuruPembimbing->value],
                self::Diterima->value => [Role::GuruPembimbing->value],
            ],
        ]);
    }
}
