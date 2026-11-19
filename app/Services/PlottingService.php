<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

final class PlottingService
{
    /** @return Collection<int, Company> */
    public function eligibleCompanies(AcademicPeriod $period, User $kaprog): Collection
    {
        $jurusanId = $kaprog->roleScopeId(Role::Kaprog);

        if ($jurusanId === null) {
            return collect();
        }

        $companies = Company::query()
            ->whereHas('pengajuanSiswa', fn (Builder $query) => $query
                ->where('academic_period_id', $period->getKey())
                ->where('status', 'sedang_pkl')
                ->whereHas('siswa', fn (Builder $student) => $student->where('jurusan_id', $jurusanId)))
            ->with(['pengajuanSiswa' => fn (Relation $query) => $query
                ->where('academic_period_id', $period->getKey())
                ->where('status', 'sedang_pkl')
                ->with('siswa')])
            ->with(['plottings' => fn (Relation $query) => $query
                ->where('academic_period_id', $period->getKey())
                ->with(['guru', 'suratTugas'])])
            ->orderBy('nama')
            ->get();

        return $companies->each(function (Company $company): void {
            $company->setAttribute('jumlah_siswa', $company->pengajuanSiswa->count());
            $company->setAttribute('jumlah_jurusan', $company->pengajuanSiswa
                ->pluck('siswa.jurusan_id')
                ->filter()
                ->unique()
                ->count());
        });
    }

    /** @return Collection<int, User> */
    public function guruCandidates(AcademicPeriod $period): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->where('role', Role::GuruPembimbing->value))
            ->withCount(['plottings as jumlah_bimbingan' => fn (Builder $query) => $query
                ->where('academic_period_id', $period->getKey())])
            ->withCount(['plottings as jumlah_perusahaan' => fn (Builder $query) => $query
                ->where('academic_period_id', $period->getKey())])
            ->with(['plottings' => fn (Relation $query) => $query
                ->where('academic_period_id', $period->getKey())
                ->with('company:id,alamat')])
            ->orderBy('name')
            ->get()
            ->each(function (User $guru): void {
                $guru->setAttribute('jumlah_wilayah', $guru->plottings
                    ->pluck('company.alamat')
                    ->filter()
                    ->map(fn ($address) => mb_strtolower(trim((string) $address)))
                    ->unique()
                    ->count());
            });
    }

    /** @return Collection<int, Company> */
    public function companiesForGuru(User $guru, ?int $periodId = null): Collection
    {
        return Company::query()
            ->whereHas('plottings', fn (Builder $query) => $query->where('guru_id', $guru->getKey())
                ->when($periodId !== null, fn (Builder $plots) => $plots->where('academic_period_id', $periodId))
                ->whereHas('company.pengajuanSiswa', fn (Builder $students) => $students
                    ->whereColumn('pengajuan_siswa.academic_period_id', 'guru_pembimbing_plotting.academic_period_id')
                    ->where('status', 'sedang_pkl')))
            ->orderBy('nama')
            ->get();
    }

    /** @return Collection<int, User> */
    public function guruCandidatesForKaprog(User $kaprog): Collection
    {
        $jurusanId = $kaprog->roleScopeId(Role::Kaprog);

        if ($jurusanId === null) {
            return collect();
        }

        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->where('role', Role::GuruPembimbing->value))
            ->whereHas('plottings', fn (Builder $plots) => $plots
                ->whereHas('company.pengajuanSiswa', fn (Builder $students) => $students
                    ->whereColumn('pengajuan_siswa.academic_period_id', 'guru_pembimbing_plotting.academic_period_id')
                    ->where('status', 'sedang_pkl')
                    ->whereHas('siswa', fn (Builder $student) => $student->where('jurusan_id', $jurusanId))))
            ->orderBy('name')
            ->get();
    }
}
