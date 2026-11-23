<?php

namespace App\Enums;

use App\Enums\Concerns\HasTransitions;
use App\Enums\Contracts\StateEnum;
use App\Enums\Transitions\TransitionTable;
use App\Support\StatusChip;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * State machine `bimbingan_submissions.status` (F6, PRD F6). Satu baris bimbingan
 * berakhir `revisi` atau `diterima`; unggahan ulang selalu baris baru.
 */
enum BimbinganStatus: string implements HasColor, HasIcon, HasLabel, StateEnum
{
    use HasTransitions;

    case MenungguFeedback = 'menunggu_feedback';
    case Revisi = 'revisi';
    case Diterima = 'diterima';

    public function label(): string
    {
        return StatusChip::label($this->value);
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return StatusChip::color($this->value);
    }

    public function getIcon(): string
    {
        return StatusChip::icon($this->value);
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
