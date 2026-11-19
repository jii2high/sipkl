<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\GuruPembimbingPlotting;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GuruPembimbingPlottingPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value);
    }

    public function assign(User $user, Company $company, AcademicPeriod $period): bool
    {
        return $this->canManagePlacements($user, $company, $period);
    }

    public function replace(User $user, GuruPembimbingPlotting $plotting): bool
    {
        if (! $this->hasActiveRole($user, Role::Kaprog->value)) {
            return false;
        }

        return $this->canManagePlacements($user, $plotting->company, $plotting->academicPeriod);
    }

    public function issueLetter(User $user, GuruPembimbingPlotting $plotting): bool
    {
        return $this->replace($user, $plotting);
    }

    private function canManagePlacements(User $user, ?Company $company, ?AcademicPeriod $period): bool
    {
        $jurusanId = $user->roleScopeId(Role::Kaprog);

        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $company !== null
            && $period !== null
            && $jurusanId !== null
            && PengajuanSiswa::query()
                ->where('company_id', $company->getKey())
                ->where('academic_period_id', $period->getKey())
                ->where('status', 'sedang_pkl')
                ->whereHas('siswa', fn (Builder $query) => $query->where('jurusan_id', $jurusanId))
                ->exists();
    }
}
