<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pengajuan_siswa_id',
    'file_path',
    'keterangan',
    'is_bagian_akhir',
    'status',
    'feedback_note',
    'reviewed_by',
    'reviewed_at',
    'is_archived',
    'archived_at',
])]
class BimbinganSubmission extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_bagian_akhir' => 'boolean',
            'reviewed_at' => 'datetime',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PengajuanSiswa, $this> */
    public function pengajuanSiswa(): BelongsTo
    {
        return $this->belongsTo(PengajuanSiswa::class, 'pengajuan_siswa_id');
    }
}
