<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'pengajuan_siswa')]
#[Fillable([
    'pengajuan_pkl_id',
    'company_id',
    'academic_period_id',
    'siswa_id',
    'status',
    'snapshot_company_json',
    'surat_balasan_path',
    'surat_balasan_hasil',
    'surat_balasan_uploaded_at',
    'accepted_by',
    'accepted_at',
    'is_provisional',
    'dokumen_fisik_diterima_at',
    'dokumen_fisik_confirmed_by',
    'last_provisional_reminder_at',
    'tanggal_mulai_pkl',
    'tanggal_selesai_pkl',
    'withdraw_requested_at',
    'withdraw_requested_by',
    'withdraw_reason',
    'withdraw_approved_by',
    'withdrawn_at',
    'resubmitted_from_id',
])]
class PengajuanSiswa extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_company_json' => 'array',
            'surat_balasan_uploaded_at' => 'datetime',
            'accepted_at' => 'datetime',
            'is_provisional' => 'boolean',
            'dokumen_fisik_diterima_at' => 'datetime',
            'last_provisional_reminder_at' => 'date',
            'tanggal_mulai_pkl' => 'date',
            'tanggal_selesai_pkl' => 'date',
            'withdraw_requested_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}
