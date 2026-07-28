<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KompetensiKeahlian extends Model
{
    protected $table = 'kompetensi_keahlian';

    protected $fillable = ['nama', 'acronym'];

    public function siswaProfiles()
    {
        return $this->hasMany(SiswaProfile::class);
    }
}
