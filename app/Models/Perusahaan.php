<?php

namespace App\Models;

use Database\Factories\PerusahaanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Perusahaan extends Model
{
    /** @use HasFactory<PerusahaanFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['nama', 'alamat', 'telp', 'email', 'kuota_saran'];

    /** @return HasMany<PengajuanPkl, $this> */
    public function pengajuanPkls(): HasMany
    {
        return $this->hasMany(PengajuanPkl::class);
    }
}
