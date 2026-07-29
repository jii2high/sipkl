<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionPenilaian extends Model
{
    protected $table = 'section_penilaian';

    protected $fillable = [
        'indikator_penilaian_id',
        'nomor_urut',
        'deskripsi',
    ];

    /** @return BelongsTo<IndikatorPenilaian, $this> */
    public function indikatorPenilaian(): BelongsTo
    {
        return $this->belongsTo(IndikatorPenilaian::class);
    }
}
