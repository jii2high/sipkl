<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'surat_tugas_visitasi')]
#[Fillable([
    'guru_id',
    'diajukan_oleh',
    'kaprog_id',
    'is_general',
    'tahun_ajaran',
    'nomor_urut',
    'nomor_surat',
    'tanggal_mulai',
    'tanggal_selesai',
    'status',
    'approved_by',
    'approved_at',
    'rejection_note',
    'signature_asset_id',
    'file_path',
])]
class SuratTugasVisitasi extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_general' => 'boolean',
            'nomor_urut' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /** @return HasMany<SuratTugasVisitasiCompany, $this> */
    public function companies(): HasMany
    {
        return $this->hasMany(SuratTugasVisitasiCompany::class, 'surat_tugas_visitasi_id');
    }
}
