<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table(name: 'jadwal_sidang')]
#[Fillable(['pengajuan_siswa_id', 'external_examiner_id', 'tanggal', 'urutan', 'status', 'scheduled_by'])]
class JadwalSidang extends Model
{
    public const STATUS_TERJADWAL = 'terjadwal';

    public const STATUS_AKTIF = 'sesi_aktif';

    public const STATUS_SELESAI = 'selesai';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'urutan' => 'integer',
        ];
    }

    /** @return BelongsTo<PengajuanSiswa, $this> */
    public function pengajuanSiswa(): BelongsTo
    {
        return $this->belongsTo(PengajuanSiswa::class);
    }

    /** @return BelongsTo<ExternalExaminer, $this> */
    public function externalExaminer(): BelongsTo
    {
        return $this->belongsTo(ExternalExaminer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    /** @return HasOne<FormSubmission, $this> */
    public function submission(): HasOne
    {
        return $this->hasOne(FormSubmission::class);
    }

    /** Nilai sudah dikunci penguji (KP-F9-1). */
    public function isScored(): bool
    {
        return (bool) $this->submission()->where('is_submitted', true)->exists();
    }
}
