<?php

namespace App\Models;

use Database\Factories\IndikatorPenilaianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndikatorPenilaian extends Model
{
    /** @use HasFactory<IndikatorPenilaianFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['kompetensi_keahlian_id', 'nomor_urut', 'nama', 'deskripsi'];

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

    public function getIsUmumAttribute(): bool
    {
        return $this->kompetensi_keahlian_id === null;
    }
}
