<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read GuruProfile|null $guruProfile
 * @property-read SiswaProfile|null $siswaProfile
 */
#[Fillable(['name', 'email', 'password', 'must_change_password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $with = ['guruProfile', 'siswaProfile'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /** @return HasOne<GuruProfile, $this> */
    public function guruProfile(): HasOne
    {
        return $this->hasOne(GuruProfile::class);
    }

    /** @return HasOne<SiswaProfile, $this> */
    public function siswaProfile(): HasOne
    {
        return $this->hasOne(SiswaProfile::class);
    }

    /** @return HasMany<PengajuanPklHistory, $this> */
    public function pengajuanHistories(): HasMany
    {
        return $this->hasMany(PengajuanPklHistory::class, 'actor_id');
    }
}
