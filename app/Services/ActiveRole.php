<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;

/**
 * Sumber tunggal otorisasi berbasis `session('active_role')` (PRD §1.5 butir 5, TASKS M0.9).
 *
 * Aturan: role aktif hanya sah bila user benar-benar memiliki baris `user_roles`
 * dengan role tersebut. Scope (jurusan/kelas) diambil dari baris itu, bukan dari users.
 */
final class ActiveRole
{
    private bool $resolved = false;

    private ?UserRole $active = null;

    public function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Nama role aktif pada sesi, atau null bila belum dipilih / tidak sah.
     */
    public function name(): ?string
    {
        return $this->role()?->role;
    }

    /**
     * Baris user_roles yang mewakili sesi aktif.
     */
    public function role(): ?UserRole
    {
        if ($this->resolved) {
            return $this->active;
        }

        $this->resolved = true;

        $user = $this->user();
        $name = session('active_role');

        if ($user === null || ! is_string($name) || $name === '') {
            return $this->active = null;
        }

        return $this->active = $user->roles()->where('role', $name)->first();
    }

    public function isValid(): bool
    {
        return $this->role() !== null;
    }

    /**
     * Scope objek untuk role aktif.
     *
     * @return array{jurusan_id: int|null, kelas_id: int|null}
     */
    public function scope(): array
    {
        $role = $this->role();

        return [
            'jurusan_id' => $role?->jurusan_id,
            'kelas_id' => $role?->kelas_id,
        ];
    }

    public function is(string ...$roles): bool
    {
        $name = $this->name();

        return $name !== null && in_array($name, $roles, true);
    }

    public function set(string $role): void
    {
        $user = $this->user();

        abort_unless($user?->roles()->where('role', $role)->exists(), 403);

        session(['active_role' => $role]);
        $this->forget();
    }

    /**
     * Bersihkan cache internal (dipakai setelah role switch).
     */
    public function forget(): void
    {
        $this->resolved = false;
        $this->active = null;
    }
}
