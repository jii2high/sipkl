<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Otorisasi periode akademik (T2.2). Hubin mengelola periode; periode yang sudah
 * punya data terkait tidak boleh dihapus (PRD F2 edge case), jadi keputusan itu
 * ditegakkan di Policy agar UI hanya mengikuti server.
 */
class AcademicPeriodPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function create(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function update(User $user, AcademicPeriod $period): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function delete(User $user, AcademicPeriod $period): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value) && ! $this->hasData($period);
    }

    /** Ada pengajuan/plotting yang menunjuk periode ini? */
    public function hasData(AcademicPeriod $period): bool
    {
        foreach (['pengajuan_siswa', 'pengajuan_pkl', 'guru_pembimbing_plotting'] as $table) {
            if (DB::table($table)->where('academic_period_id', $period->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }
}
