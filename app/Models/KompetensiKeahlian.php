<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read SiswaProfile[] $siswaProfiles
 */
class KompetensiKeahlian extends Model
{
    protected $table = 'kompetensi_keahlian';

    protected $fillable = ['nama', 'acronym'];

    /** @return HasMany<SiswaProfile, $this> */
    public function siswaProfiles(): HasMany
    {
        return $this->hasMany(SiswaProfile::class);
    }

    /** @return HasMany<GuruProfile, $this> */
    public function guruProfiles(): HasMany
    {
        return $this->hasMany(GuruProfile::class);
    }
}
