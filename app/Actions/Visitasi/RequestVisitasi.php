<?php

namespace App\Actions\Visitasi;

use App\Actions\Action;
use App\Enums\Role;
use App\Models\GuruPembimbingPlotting;
use App\Models\PengajuanSiswa;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Support\JakartaTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class RequestVisitasi extends Action
{
    public function handle(mixed ...$arguments): SuratTugasVisitasi
    {
        [$guru, $companyIds, $isGeneral, $actor] = $arguments;

        if (! $guru instanceof User || ! $actor instanceof User || ! is_array($companyIds) || ! is_bool($isGeneral)) {
            throw new RuntimeException('Guru, perusahaan, jenis visitasi, dan pengaju wajib diisi.');
        }

        Gate::forUser($actor)->authorize('create', SuratTugasVisitasi::class);

        if (! $guru->is_active || ! $guru->hasRole(Role::GuruPembimbing)) {
            throw ValidationException::withMessages(['guruId' => 'Pilih guru pembimbing yang masih aktif.']);
        }

        $companyIds = array_values(array_unique(array_map('intval', $companyIds)));

        if ($isGeneral) {
            $companyIds = [];
        }

        if (! $isGeneral && $companyIds === []) {
            throw ValidationException::withMessages(['companyIds' => 'Pilih minimal satu perusahaan atau pilih visitasi umum.']);
        }

        if ($isGeneral && ! GuruPembimbingPlotting::query()
            ->where('guru_id', $guru->getKey())
            ->whereHas('academicPeriod', fn (Builder $period) => $period->where('is_active', true))
            ->whereHas('company.pengajuanSiswa', fn (Builder $query) => $query
                ->whereColumn('pengajuan_siswa.academic_period_id', 'guru_pembimbing_plotting.academic_period_id')
                ->where('status', 'sedang_pkl'))
            ->exists()) {
            throw ValidationException::withMessages(['guruId' => 'Guru belum memiliki perusahaan bimbingan.']);
        }

        foreach ($companyIds as $companyId) {
            $plot = GuruPembimbingPlotting::query()
                ->where('guru_id', $guru->getKey())
                ->where('company_id', $companyId)
                ->whereHas('academicPeriod', fn (Builder $period) => $period->where('is_active', true))
                ->whereHas('company.pengajuanSiswa', fn (Builder $query) => $query
                    ->whereColumn('pengajuan_siswa.academic_period_id', 'guru_pembimbing_plotting.academic_period_id')
                    ->where('status', 'sedang_pkl'))
                ->exists();

            if (! $plot || ! $this->companyInActorScope($actor, $companyId)) {
                throw ValidationException::withMessages(['companyIds' => 'Perusahaan harus memiliki siswa PKL aktif dan berada dalam lingkup pengaju.']);
            }
        }

        if (session('active_role') === Role::Kaprog->value && ! $this->guruInActorScope($actor, $guru)) {
            throw ValidationException::withMessages(['guruId' => 'Guru tidak memiliki perusahaan PKL dalam program keahlian Anda.']);
        }

        return $this->transaction(function () use ($guru, $companyIds, $isGeneral, $actor): SuratTugasVisitasi {
            $start = JakartaTime::today();
            $end = $start->addDays((int) config('pkl.visitasi.default_validity_days', 7) - 1);
            $isKaprog = session('active_role') === Role::Kaprog->value;

            $visitasi = SuratTugasVisitasi::query()->create([
                'guru_id' => $guru->getKey(),
                'diajukan_oleh' => $isKaprog ? Role::Kaprog->value : Role::Hubin->value,
                'kaprog_id' => $isKaprog ? $actor->getKey() : null,
                'is_general' => $isGeneral,
                'tanggal_mulai' => $start->toDateString(),
                'tanggal_selesai' => $end->toDateString(),
                'status' => 'diajukan',
            ]);

            foreach ($companyIds as $companyId) {
                $visitasi->companies()->create(['company_id' => $companyId]);
            }

            $this->audit(
                $visitasi,
                'visitasi_diajukan',
                null,
                'diajukan',
                ['guru_id' => $guru->getKey(), 'company_ids' => $companyIds, 'is_general' => $isGeneral],
                'Pengajuan visitasi dibuat',
            );

            return $visitasi->load(['guru', 'companies.company']);
        });
    }

    private function companyInActorScope(User $actor, int $companyId): bool
    {
        if (session('active_role') !== Role::Kaprog->value) {
            return true;
        }

        $jurusanId = $actor->roleScopeId(Role::Kaprog);

        return $jurusanId !== null && PengajuanSiswa::query()
            ->where('company_id', $companyId)
            ->where('status', 'sedang_pkl')
            ->whereHas('siswa', fn (Builder $query) => $query->where('jurusan_id', $jurusanId))
            ->exists();
    }

    private function guruInActorScope(User $actor, User $guru): bool
    {
        $jurusanId = $actor->roleScopeId(Role::Kaprog);

        if ($jurusanId === null) {
            return false;
        }

        return GuruPembimbingPlotting::query()
            ->where('guru_id', $guru->getKey())
            ->whereHas('academicPeriod', fn (Builder $period) => $period->where('is_active', true))
            ->whereHas('company.pengajuanSiswa', fn (Builder $query) => $query
                ->whereColumn('pengajuan_siswa.academic_period_id', 'guru_pembimbing_plotting.academic_period_id')
                ->where('status', 'sedang_pkl')
                ->whereHas('siswa', fn (Builder $student) => $student->where('jurusan_id', $jurusanId)))
            ->exists();
    }
}
