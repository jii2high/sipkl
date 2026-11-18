<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'token_hash',
    'document_type',
    'document_id',
    'nomor_surat',
    'status',
    'issued_at',
    'revoked_at',
    'revoked_by',
    'revoke_reason',
])]
class DocumentQrToken extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
