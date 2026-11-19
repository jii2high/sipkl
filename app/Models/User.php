<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\Theme;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'nis_nip',
    'name',
    'email',
    'password',
    'user_type',
    'jurusan_id',
    'kelas_id',
    'is_first_login',
    'is_active',
    'theme_preference',
])]
#[Hidden(['password'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<int, string>|null Cache per request daftar peran pengguna. */
    private ?array $roleCache = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_first_login' => 'boolean',
            'is_active' => 'boolean',
            'theme_preference' => Theme::class,
        ];
    }

    // Tabel `users` (docs/schema.sql) tidak punya kolom `remember_token`: sesi login
    // selalu berbasis sesi server, tanpa cookie "ingat saya" (PRD F1 tidak memintanya).
    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        //
    }

    /**
     * Tujuan email notifikasi. Di lingkungan `local`, seluruh email dialihkan ke
     * `pkl.mail.override_to` (bila diisi) agar OTP dapat diuji tanpa domain sekolah.
     *
     * @return array<string, string>|string
     */
    public function routeNotificationForMail(): array|string
    {
        $override = config('pkl.mail.override_to');

        if (config('app.env') === 'local' && is_string($override) && $override !== '') {
            return [$override => $this->name];
        }

        return $this->email;
    }

    /**
     * @return HasMany<UserRole, $this>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * @return HasMany<OtpCode, $this>
     */
    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    /** @return HasMany<GuruPembimbingPlotting, $this> */
    public function plottings(): HasMany
    {
        return $this->hasMany(GuruPembimbingPlotting::class, 'guru_id');
    }

    /** @return BelongsTo<Jurusan, $this> */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class);
    }

    /** @return BelongsTo<Kelas, $this> */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Daftar nilai peran (string) milik pengguna; di-cache per instance.
     *
     * @return array<int, string>
     */
    public function roleValues(): array
    {
        return $this->roleCache ??= $this->roles()->pluck('role')->all();
    }

    public function hasRole(Role|string ...$roles): bool
    {
        $wanted = array_map(fn (Role|string $role) => $role instanceof Role ? $role->value : $role, $roles);

        return array_intersect($wanted, $this->roleValues()) !== [];
    }

    /**
     * Id scope peran (jurusan untuk Kaprog, kelas untuk Walas) dari `user_roles`.
     * Peran tanpa scope atau baris peran yang tidak ada menghasilkan null.
     */
    public function roleScopeId(Role|string $role): ?int
    {
        $role = $role instanceof Role ? $role->value : $role;

        $row = $this->roles()->where('role', $role)->first();

        return match ($role) {
            Role::Kaprog->value => $row?->jurusan_id !== null ? (int) $row->jurusan_id : null,
            Role::Walas->value => $row?->kelas_id !== null ? (int) $row->kelas_id : null,
            default => null,
        };
    }

    /** Peran aktif default setelah login, menurut prioritas di `Role::defaultPriority()`. */
    public function defaultActiveRole(): ?string
    {
        $owned = $this->roleValues();

        foreach (Role::defaultPriority() as $role) {
            if (in_array($role->value, $owned, true)) {
                return $role->value;
            }
        }

        return null;
    }

    /** URL panel untuk peran (Hubin di `/hubin`, sisanya di `/app`). */
    public function panelHomeUrl(?string $role = null): string
    {
        $role ??= $this->defaultActiveRole();

        return $role === Role::Hubin->value ? url('/hubin') : url('/app');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'hubin' => $this->hasRole(Role::Hubin),
            'app' => $this->hasRole(
                Role::Siswa,
                Role::Walas,
                Role::GuruPembimbing,
                Role::Kaprog,
                Role::Kepsek,
            ),
            default => false,
        };
    }
}
