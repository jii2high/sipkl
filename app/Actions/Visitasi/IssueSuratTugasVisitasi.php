<?php

namespace App\Actions\Visitasi;

use App\Actions\Action;
use App\Models\AcademicPeriod;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

final class IssueSuratTugasVisitasi extends Action
{
    public function __construct(private readonly DocumentService $documents) {}

    public function handle(mixed ...$arguments): SuratTugasVisitasi
    {
        [$visitasi, $hubin] = $arguments;

        if (! $visitasi instanceof SuratTugasVisitasi || ! $hubin instanceof User) {
            throw new RuntimeException('Surat visitasi dan Hubin wajib diisi.');
        }

        Gate::forUser($hubin)->authorize('issue', $visitasi);

        if (! in_array($visitasi->status, ['disetujui', 'dicetak', 'aktif'], true)) {
            throw new RuntimeException('Visitasi harus disetujui sebelum surat diterbitkan.');
        }

        return $this->issue($visitasi, $hubin);
    }

    /** Dipanggil ApproveVisitasi agar nomor terbit sebelum status berubah. */
    public function issueForApproval(SuratTugasVisitasi $visitasi, User $hubin): SuratTugasVisitasi
    {
        Gate::forUser($hubin)->authorize('approve', $visitasi);

        return $this->issue($visitasi, $hubin);
    }

    private function issue(SuratTugasVisitasi $visitasi, User $hubin): SuratTugasVisitasi
    {
        if ($visitasi->nomor_surat !== null) {
            return $visitasi;
        }

        $year = AcademicPeriod::query()
            ->where('is_active', true)
            ->orderByDesc('tanggal_mulai')
            ->value('tahun_ajaran');

        if (! is_string($year) || $year === '') {
            $year = $visitasi->guru?->plottings()
                ->whereHas('academicPeriod', fn ($query) => $query->where('tahun_ajaran', '!=', ''))
                ->with('academicPeriod')
                ->latest('plotted_at')
                ->first()?->academicPeriod?->tahun_ajaran;
        }

        if (! is_string($year) || $year === '') {
            throw new RuntimeException('Tahun ajaran aktif belum tersedia.');
        }

        return $this->documents->issue($visitasi, DocumentService::JENIS_TUGAS_VISITASI, $year, $hubin);
    }
}
