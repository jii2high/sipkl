<?php

namespace App\Models;

use Database\Factories\SubSectionPenilaianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubSectionPenilaian extends Model
{
    /** @use HasFactory<SubSectionPenilaianFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['section_penilaian_id', 'nomor_urut', 'nama', 'deskripsi'];

    /** @return BelongsTo<SectionPenilaian, $this> */
    public function sectionPenilaian(): BelongsTo
    {
        return $this->belongsTo(SectionPenilaian::class);
    }

    /** @return HasMany<NilaiPenilaian, $this> */
    public function nilaiPenilaians(): HasMany
    {
        return $this->hasMany(NilaiPenilaian::class, 'sub_section_penilaian_id');
    }
}
