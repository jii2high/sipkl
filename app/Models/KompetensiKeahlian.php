<?php

namespace App\Models;

use Database\Factories\KompetensiKeahlianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read SiswaProfile[] $siswaProfiles
 */
class KompetensiKeahlian extends Model
{
    /** @use HasFactory<KompetensiKeahlianFactory> */
    use HasFactory;

    protected $table = 'kompetensi_keahlian';

    protected $fillable = ['nama', 'acronym'];

    /** @return HasMany<SiswaProfile, $this> */
    public function siswaProfiles(): HasMany
    {
        return $this->hasMany(SiswaProfile::class);
    }
}
