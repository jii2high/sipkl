<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndikatorPenilaian extends Model
{
    protected $table = 'indikator_penilaian';

    protected $fillable = [
        'kategori',
        'kompetensi_keahlian_id',
        'nomor_urut',
        'deskripsi',
    ];

    public function kompetensiKeahlian()
    {
        return $this->belongsTo(KompetensiKeahlian::class);
    }

    public function sections()
    {
        return $this->hasMany(SectionPenilaian::class);
    }
}
