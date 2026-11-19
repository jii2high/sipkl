<?php

namespace App\Actions\Plotting;

use App\Actions\Action;
use App\Exceptions\DocumentIssuanceHeld;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\GuruPembimbingPlotting;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class AssignGuru extends Action
{
    public function __construct(private readonly IssueSuratTugasPembimbing $letters) {}

    public function handle(mixed ...$arguments): GuruPembimbingPlotting
    {
        [$company, $period, $guru, $kaprog] = $arguments;

        if (! $company instanceof Company || ! $period instanceof AcademicPeriod
            || ! $guru instanceof User || ! $kaprog instanceof User) {
            throw new RuntimeException('Perusahaan, periode, guru, dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('assign', [GuruPembimbingPlotting::class, $company, $period]);

        $this->assertEligibleGuru($guru);

        return $this->transaction(function () use ($company, $period, $guru, $kaprog): GuruPembimbingPlotting {
            Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();

            $existing = GuruPembimbingPlotting::query()
                ->where('company_id', $company->getKey())
                ->where('academic_period_id', $period->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ((int) $existing->guru_id === (int) $guru->getKey()) {
                    return $existing->load(['guru', 'company', 'academicPeriod', 'suratTugas']);
                }

                return app(ReplaceGuru::class)->handle($existing, $guru, $kaprog);
            }

            $jurusanId = $kaprog->roleScopeId('kaprog');
            $eligible = PengajuanSiswa::query()
                ->where('company_id', $company->getKey())
                ->where('academic_period_id', $period->getKey())
                ->where('status', 'sedang_pkl')
                ->whereHas('siswa', fn (Builder $query) => $query->where('jurusan_id', $jurusanId))
                ->exists();

            if (! $eligible) {
                throw ValidationException::withMessages([
                    'companyId' => 'Perusahaan belum memiliki siswa PKL aktif dalam program keahlian Anda.',
                ]);
            }

            $plotting = GuruPembimbingPlotting::query()->create([
                'company_id' => $company->getKey(),
                'academic_period_id' => $period->getKey(),
                'guru_id' => $guru->getKey(),
                'plotted_by' => $kaprog->getKey(),
                'plotted_at' => now(),
            ]);

            $this->audit(
                $plotting,
                'guru_diplot',
                null,
                (string) $guru->getKey(),
                ['company_id' => $company->getKey(), 'academic_period_id' => $period->getKey(), 'guru_id' => $guru->getKey()],
                'Guru pembimbing ditetapkan',
            );

            try {
                $this->letters->handle($plotting, $kaprog);
            } catch (DocumentIssuanceHeld) {
                // Plotting tetap berlaku; DocumentService already notified Hubin.
            }

            return $plotting->load(['guru', 'company', 'academicPeriod', 'suratTugas']);
        });
    }

    private function assertEligibleGuru(User $guru): void
    {
        if (! $guru->is_active || ! $guru->hasRole('guru_pembimbing')) {
            throw ValidationException::withMessages([
                'guruId' => 'Pilih guru pembimbing yang masih aktif.',
            ]);
        }
    }
}
