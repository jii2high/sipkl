<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'pengajuan_siswa_id',
    'laporan_version_id',
    'step_order',
    'signer_type',
    'signer_user_id',
    'signer_external_name',
    'signer_external_contact',
    'status',
    'signed_at',
    'rejection_note',
])]
class DocumentSignature extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'signed_at' => 'datetime',
        ];
    }
}
