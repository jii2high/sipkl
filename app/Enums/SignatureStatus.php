<?php

namespace App\Enums;

use App\Enums\Concerns\HasTransitions;
use App\Enums\Contracts\StateEnum;
use App\Enums\Transitions\TransitionTable;

/**
 * State machine `document_signatures.status` (TRD.md §5.4). Satu baris berubah
 * tepat sekali dari `pending` menjadi `signed` atau `rejected`; revisi memakai
 * baris baru, bukan mengembalikan status.
 */
enum SignatureStatus: string implements StateEnum
{
    use HasTransitions;

    case Pending = 'pending';

    case Signed = 'signed';

    case Rejected = 'rejected';

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
