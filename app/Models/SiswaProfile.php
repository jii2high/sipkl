<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User $user
 * @property-read KompetensiKeahlian|null $kompetensiKeahlian
 */
class SiswaProfile extends Model
{
    protected $table = 'siswa_profiles';

    protected $fillable = ['user_id', 'nis', 'kelas', 'kompetensi_keahlian_id', 'alamat', 'no_telp'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<KompetensiKeahlian, $this> */
    public function kompetensiKeahlian(): BelongsTo
    {
        return $this->belongsTo(KompetensiKeahlian::class);
    }
}
