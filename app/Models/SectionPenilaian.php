<?php

namespace App\Models;

use Database\Factories\SectionPenilaianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectionPenilaian extends Model
{
    /** @use HasFactory<SectionPenilaianFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['indikator_penilaian_id', 'nomor_urut', 'nama', 'deskripsi'];

    /** @return BelongsTo<IndikatorPenilaian, $this> */
    public function indikatorPenilaian(): BelongsTo
    {
        return $this->belongsTo(IndikatorPenilaian::class);
    }

    /** @return HasMany<SubSectionPenilaian, $this> */
    public function subSections(): HasMany
    {
        return $this->hasMany(SubSectionPenilaian::class, 'section_penilaian_id');
    }
}
