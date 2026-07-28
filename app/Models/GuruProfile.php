<?php

namespace App\Models;

use Database\Factories\GuruProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User $user
 * @property-read KompetensiKeahlian|null $kompetensiKeahlian
 */
class GuruProfile extends Model
{
    /** @use HasFactory<GuruProfileFactory> */
    use HasFactory;

    protected $table = 'guru_profiles';

    protected $fillable = ['user_id', 'nip', 'jabatan', 'kompetensi_keahlian_id'];

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
