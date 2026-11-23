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
 * State machine `document_signatures.status` (TRD.md §5.4). Satu baris berubah
 * tepat sekali dari `pending` menjadi `signed` atau `rejected`; revisi memakai
 * baris baru, bukan mengembalikan status.
 */
enum SignatureStatus: string implements HasColor, HasIcon, HasLabel, StateEnum
{
    use HasTransitions;

    case Pending = 'pending';

    case Signed = 'signed';

    case Rejected = 'rejected';

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
        $signers = [
            'guru_pembimbing',
            'kaprog',
            'pembimbing_industri',
            'kepala_industri',
            'kepsek',
        ];

        return new TransitionTable([
            self::Pending->value => [
                self::Signed->value => $signers,
                self::Rejected->value => $signers,
            ],
        ]);
    }
}
