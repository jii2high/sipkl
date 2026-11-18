<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'form_submission_id',
    'jawaban_lama',
    'jawaban_baru',
    'reported_by',
    'reason',
    'approved_by_kaprog_id',
    'unlock_magic_link_id',
    'unlock_expires_at',
    'status',
])]
class NilaiSidangRevision extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jawaban_lama' => 'array',
            'jawaban_baru' => 'array',
            'unlock_expires_at' => 'datetime',
        ];
    }
}
