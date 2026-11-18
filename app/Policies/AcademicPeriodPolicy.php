<?php

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\User;
use App\Policies\Concerns\ScopesByActiveRole;
use App\Services\AcademicPeriodGuard;

/**
 * Otorisasi master data periode akademik (TASKS M2.6).
 *
 * Hanya `active_role=hubin` yang boleh mengelola periode. Larangan hapus
 * periode berdata terkait ditegakkan di `AcademicPeriodGuard` (model event),
 * bukan hanya di UI.
 */
class AcademicPeriodPolicy
{
    use ScopesByActiveRole;

    public function viewAny(User $user): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function view(User $user, AcademicPeriod $period): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function create(User $user): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function update(User $user, AcademicPeriod $period): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function delete(User $user, AcademicPeriod $period): bool
    {
        return $this->activeRole()->is('hubin')
            && ! app(AcademicPeriodGuard::class)->punyaDataTerkait($period);
    }

    public function deleteAny(User $user): bool
    {
        return $this->activeRole()->is('hubin');
    }
}
