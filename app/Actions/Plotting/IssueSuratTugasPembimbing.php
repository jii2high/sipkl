<?php

namespace App\Actions\Plotting;

use App\Actions\Action;
use App\Models\GuruPembimbingPlotting;
use App\Models\SuratTugasPembimbing;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

final class IssueSuratTugasPembimbing extends Action
{
    public function __construct(private readonly DocumentService $documents) {}

    public function handle(mixed ...$arguments): SuratTugasPembimbing
    {
        [$plotting, $kaprog] = $arguments;

        if (! $plotting instanceof GuruPembimbingPlotting || ! $kaprog instanceof User) {
            throw new RuntimeException('Plotting dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('issueLetter', $plotting);

        $current = $plotting->suratTugas()->first();

        if ($current !== null) {
            return $current;
        }

        $tahunAjaran = $plotting->academicPeriod?->tahun_ajaran;

        if (! is_string($tahunAjaran) || $tahunAjaran === '') {
            throw new RuntimeException('Tahun ajaran periode belum tersedia.');
        }

        return $this->documents->issue(
            new SuratTugasPembimbing(['guru_pembimbing_plotting_id' => $plotting->getKey()]),
            DocumentService::JENIS_TUGAS_PEMBIMBING,
            $tahunAjaran,
            $kaprog,
        );
    }
}
