<?php

namespace App\Models;

use Database\Factories\IndikatorPenilaianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndikatorPenilaian extends Model
{
    /** @use HasFactory<IndikatorPenilaianFactory> */
    use HasFactory;

    protected $fillable = ['kategori', 'kompetensi_keahlian_id', 'nomor_urut', 'deskripsi'];

    /** @return BelongsTo<KompetensiKeahlian, $this> */
    public function kompetensiKeahlian(): BelongsTo
    {
        return $this->belongsTo(KompetensiKeahlian::class);
    }

    /** @return HasMany<SectionPenilaian, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(SectionPenilaian::class, 'indikator_penilaian_id');
    }
}
