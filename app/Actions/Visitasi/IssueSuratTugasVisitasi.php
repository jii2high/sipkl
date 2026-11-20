<?php

namespace App\Actions\Visitasi;

use App\Actions\Action;
use App\Models\AcademicPeriod;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Services\DocumentService;
use App\Support\JakartaTime;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Terbitkan surat tugas visitasi (F5). Masa berlaku dibekukan saat nomor
 * dialokasikan, jadi surat yang tertahan beberapa hari tetap berlaku penuh sejak
 * terbit (Q-L). Idempoten: surat yang sudah bernomor mengembalikan baris yang ada
 * tanpa mengambil nomor atau menggeser tanggal.
 */
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

        $tahunAjaran = $this->resolveTahunAjaran($visitasi);

        if ($visitasi->nomor_surat !== null) {
            return $visitasi;
        }

        // TTD Kepsek kosong: biarkan DocumentService memberi tahu Hubin lalu melempar
        // di luar transaksi, agar notifikasi tidak ikut dibatalkan saat transaksi gagal.
        if ($this->documents->activeSignature() === null) {
            return $this->documents->issue($visitasi, DocumentService::JENIS_TUGAS_VISITASI, $tahunAjaran, $hubin);
        }

        // Tanggal dan nomor dibekukan di transaksi yang sama supaya retry tidak
        // menggeser masa berlaku; DocumentService memakai transaksi bersarang.
        return $this->transaction(function () use ($visitasi, $hubin, $tahunAjaran): SuratTugasVisitasi {
            $locked = SuratTugasVisitasi::query()->whereKey($visitasi->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->nomor_surat !== null) {
                return $locked;
            }

            $start = JakartaTime::today();
            $end = $start->addDays((int) config('pkl.visitasi.default_validity_days', 7) - 1);

            $locked->newQuery()->whereKey($locked->getKey())->update([
                'tanggal_mulai' => $start->toDateString(),
                'tanggal_selesai' => $end->toDateString(),
            ]);
            $locked->refresh();

            return $this->documents->issue($locked, DocumentService::JENIS_TUGAS_VISITASI, $tahunAjaran, $hubin);
        });
    }

    private function resolveTahunAjaran(SuratTugasVisitasi $visitasi): string
    {
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

        return $year;
    }
}
