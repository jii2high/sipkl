<?php

namespace App\Models;

use Database\Factories\SectionPenilaianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionPenilaian extends Model
{
    /** @use HasFactory<SectionPenilaianFactory> */
    use HasFactory;

    protected $fillable = ['indikator_penilaian_id', 'nomor_urut', 'deskripsi'];

    /** @return BelongsTo<IndikatorPenilaian, $this> */
    public function indikatorPenilaian(): BelongsTo
    {
        return $this->belongsTo(IndikatorPenilaian::class);
    }
}
