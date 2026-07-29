<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionPenilaian extends Model
{
    protected $table = 'section_penilaian';

    protected $fillable = [
        'indikator_penilaian_id',
        'nomor_urut',
        'deskripsi',
    ];

    public function indikatorPenilaian()
    {
        return $this->belongsTo(IndikatorPenilaian::class);
    }
}
