<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'pengajuan_pkl')]
#[Fillable([
    'company_id',
    'academic_period_id',
    'status',
    'tahun_ajaran',
    'nomor_urut',
    'nomor_surat',
    'signature_asset_id',
    'file_path',
    'issued_by',
    'issued_at',
    'sent_at',
])]
class PengajuanPkl extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nomor_urut' => 'integer',
            'issued_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<AcademicPeriod, $this> */
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /** @return HasMany<PengajuanSiswa, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(PengajuanSiswa::class, 'pengajuan_pkl_id');
    }
}
