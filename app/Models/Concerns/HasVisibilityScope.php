<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Batas visibilitas baris per peran aktif (TRD.md §7.3): daftar dibatasi di query,
 * bukan disembunyikan di UI. Konvensi: model menyediakan scope `scopeForNamaPeran`,
 * mis. `scopeForWalas(Builder $query, User $user)`. Bila peran tidak dikenal atau
 * tidak punya scope, hasilnya kosong (deny-by-default).
 *
 * Pemakaian: `PengajuanSiswa::query()->visibleTo($user, $activeRole)`.
 */
trait HasVisibilityScope
{
    public function scopeVisibleTo(Builder $query, User $user, ?string $role = null): Builder
    {
        if ($role === null || $role === '') {
            return $query->whereRaw('1 = 0');
        }

        $method = 'scopeFor'.Str::studly($role);

        if (! method_exists($this, $method)) {
            return $query->whereRaw('1 = 0');
        }

        return $this->{$method}($query, $user);
    }
}
