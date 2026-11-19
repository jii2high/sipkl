<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nama',
    'alamat',
    'email',
    'website',
    'telp_fax',
    'cp_name',
    'cp_phone',
    'pembimbing_industri_nama',
    'pembimbing_industri_email',
    'pembimbing_industri_email_verified_at',
    'kepala_industri_nama',
    'kepala_industri_email',
    'kepala_industri_email_verified_at',
    'source',
    'created_by',
    'merged_into_id',
])]
class Company extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pembimbing_industri_email_verified_at' => 'datetime',
            'kepala_industri_email_verified_at' => 'datetime',
        ];
    }

    /** @return HasMany<PengajuanSiswa, $this> */
    public function pengajuanSiswa(): HasMany
    {
        return $this->hasMany(PengajuanSiswa::class);
    }

    /** @return HasMany<GuruPembimbingPlotting, $this> */
    public function plottings(): HasMany
    {
        return $this->hasMany(GuruPembimbingPlotting::class);
    }
}
