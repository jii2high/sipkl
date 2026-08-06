<?php

namespace App\Models;

use Database\Factories\NilaiPenilaianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiPenilaian extends Model
{
    /** @use HasFactory<NilaiPenilaianFactory> */
    use HasFactory;

    protected $table = 'nilai_penilaian';

    protected $fillable = [
        'formulir_penilaian_id',
        'indikator_penilaian_id',
        'section_penilaian_id',
        'nilai',
        'predikat',
    ];

    /** @return BelongsTo<FormulirPenilaian, $this> */
    public function formulirPenilaian(): BelongsTo
    {
        return $this->belongsTo(FormulirPenilaian::class, 'formulir_penilaian_id');
    }

    /** @return BelongsTo<IndikatorPenilaian, $this> */
    public function indikatorPenilaian(): BelongsTo
    {
        return $this->belongsTo(IndikatorPenilaian::class);
    }

    /** @return BelongsTo<SectionPenilaian, $this> */
    public function sectionPenilaian(): BelongsTo
    {
        return $this->belongsTo(SectionPenilaian::class);
    }
}
