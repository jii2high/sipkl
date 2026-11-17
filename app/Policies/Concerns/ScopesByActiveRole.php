<?php

namespace App\Policies\Concerns;

use App\Services\ActiveRole;

trait ScopesByActiveRole
{
    protected function activeRole(): ActiveRole
    {
        return app(ActiveRole::class);
    }

    public function isKaprogOf(?int $jurusanId): bool
    {
        $role = $this->activeRole()->role();

        return $role !== null
            && $role->role === 'kaprog'
            && $role->jurusan_id !== null
            && $role->jurusan_id === $jurusanId;
    }

    public function isWalasOf(?int $kelasId): bool
    {
        $role = $this->activeRole()->role();

        return $role !== null
            && $role->role === 'walas'
            && $role->kelas_id !== null
            && $role->kelas_id === $kelasId;
    }

    public function isGuruOf(mixed $target): bool
    {
        $role = $this->activeRole()->role();

        if ($role === null || $role->role !== 'guru_pembimbing') {
            return false;
        }

        $kelasId = is_object($target)
            ? ($target->kelas_id ?? $target->kelas?->id ?? null)
            : $target;

        return $role->kelas_id !== null && $role->kelas_id === $kelasId;
    }
}
