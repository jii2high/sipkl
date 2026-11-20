<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** @return BelongsTo<PengajuanSiswa, $this> */
    public function pengajuanSiswa(): BelongsTo
    {
        return $this->belongsTo(PengajuanSiswa::class, 'pengajuan_siswa_id');
    }

    /** @return BelongsTo<LaporanVersion, $this> */
    public function laporanVersion(): BelongsTo
    {
        return $this->belongsTo(LaporanVersion::class, 'laporan_version_id');
    }
}
