<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\GuruPembimbingPlotting;
use App\Models\User;

/**
 * Nilai sidang yang sudah dikunci hanya dapat dibaca/diberi kelulusan oleh Kaprog
 * program keahlian siswa (F9, Q-I). Penilaian industri dikelola guru pembimbing
 * perusahaan itu, dengan override Hubin (F10).
 */
class FormSubmissionPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value);
    }

    public function setKelulusan(User $user, FormSubmission $submission): bool
    {
        $scope = $user->roleScopeId(Role::Kaprog);
        $jurusanId = $submission->pengajuanSiswa?->siswa?->jurusan_id;

        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $scope !== null
            && $jurusanId !== null
            && (int) $jurusanId === $scope;
    }

    /** Guru yang terplot pada perusahaan+periode itu, atau Hubin (override). */
    public function issueIndustryLink(User $user, Company $company, AcademicPeriod $period): bool
    {
        if ($this->hasActiveRole($user, Role::Hubin->value)) {
            return true;
        }

        return $this->hasActiveRole($user, Role::GuruPembimbing->value)
            && $this->plotted($user, (int) $company->getKey(), (int) $period->getKey());
    }

    public function revokeIndustryLink(User $user, FormSubmission $submission): bool
    {
        return $this->ownsIndustrySubmission($user, $submission);
    }

    public function reissueIndustryLink(User $user, FormSubmission $submission): bool
    {
        return $this->ownsIndustrySubmission($user, $submission);
    }

    private function ownsIndustrySubmission(User $user, FormSubmission $submission): bool
    {
        if ($submission->form_type !== FormDefinition::TYPE_INDUSTRI) {
            return false;
        }

        if ($this->hasActiveRole($user, Role::Hubin->value)) {
            return true;
        }

        return $this->hasActiveRole($user, Role::GuruPembimbing->value)
            && $submission->company_id !== null
            && $submission->academic_period_id !== null
            && $this->plotted($user, (int) $submission->company_id, (int) $submission->academic_period_id);
    }

    private function plotted(User $user, int $companyId, int $periodId): bool
    {
        return GuruPembimbingPlotting::query()
            ->where('company_id', $companyId)
            ->where('academic_period_id', $periodId)
            ->where('guru_id', $user->getKey())
            ->exists();
    }
}
