<?php

namespace App\Models;

use Database\Factories\PeriodePklFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodePkl extends Model
{
    /** @use HasFactory<PeriodePklFactory> */
    use HasFactory;

    protected $fillable = ['nama', 'tanggal_mulai', 'tanggal_selesai', 'status_aktif'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status_aktif' => 'boolean',
        ];
    }

    /** @return HasMany<PengajuanPkl, $this> */
    public function pengajuanPkls(): HasMany
    {
        return $this->hasMany(PengajuanPkl::class, 'periode_id');
    }
}
