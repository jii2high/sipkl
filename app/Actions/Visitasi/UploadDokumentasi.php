<?php

namespace App\Actions\Visitasi;

use App\Actions\Action;
use App\Models\GuruPembimbingPlotting;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Models\VisitasiDokumentasi;
use App\Services\FileStorageService;
use App\Support\JakartaTime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class UploadDokumentasi extends Action
{
    public function __construct(private readonly FileStorageService $files) {}

    public function handle(mixed ...$arguments): VisitasiDokumentasi
    {
        [$visitasi, $companyId, $date, $file, $guru, $note] = $arguments;

        if (! $visitasi instanceof SuratTugasVisitasi || ! $guru instanceof User || ! $file instanceof UploadedFile) {
            throw new RuntimeException('Visitasi, perusahaan, tanggal, foto, dan guru wajib diisi.');
        }

        Gate::forUser($guru)->authorize('upload', $visitasi);
        $companyId = (int) $companyId;
        $visitasi->refresh();
        $today = JakartaTime::today()->toDateString();

        if ($visitasi->tanggal_selesai->toDateString() < $today) {
            throw ValidationException::withMessages(['visitasi' => 'Masa berlaku surat visitasi sudah berakhir.']);
        }

        if ($date < $visitasi->tanggal_mulai->toDateString() || $date > $visitasi->tanggal_selesai->toDateString()) {
            throw ValidationException::withMessages(['visitDate' => 'Tanggal kunjungan harus berada dalam masa berlaku surat.']);
        }

        $isListedCompany = $visitasi->is_general
            || $visitasi->companies()->where('company_id', $companyId)->exists();
        $isGuruCompany = GuruPembimbingPlotting::query()
            ->where('guru_id', $guru->getKey())
            ->where('company_id', $companyId)
            ->whereHas('company.pengajuanSiswa', fn ($query) => $query
                ->whereColumn('pengajuan_siswa.academic_period_id', 'guru_pembimbing_plotting.academic_period_id')
                ->where('status', 'sedang_pkl'))
            ->exists();

        if (! $isListedCompany || ! $isGuruCompany) {
            throw ValidationException::withMessages(['companyId' => 'Perusahaan bukan bagian dari bimbingan guru ini.']);
        }

        $this->files->assertAcceptable($file, 'visitasi');
        $path = $this->files->store($file, 'visitasi', 'visitasi');

        try {
            return $this->transaction(function () use ($visitasi, $companyId, $date, $guru, $note, $path): VisitasiDokumentasi {
                $locked = SuratTugasVisitasi::query()->whereKey($visitasi->getKey())->lockForUpdate()->firstOrFail();

                if (! in_array($locked->status, ['disetujui', 'dicetak', 'aktif'], true)
                    || $locked->tanggal_selesai->toDateString() < JakartaTime::today()->toDateString()) {
                    throw ValidationException::withMessages(['visitasi' => 'Masa berlaku surat visitasi sudah berakhir.']);
                }

                $documentation = VisitasiDokumentasi::query()->create([
                    'surat_tugas_visitasi_id' => $locked->getKey(),
                    'company_id' => $companyId,
                    'uploaded_by' => $guru->getKey(),
                    'tanggal_kunjungan' => $date,
                    'file_path' => $path,
                    'keterangan' => filled($note) ? trim((string) $note) : null,
                ]);

                $this->audit(
                    $documentation,
                    'dokumentasi_visitasi_diunggah',
                    null,
                    'diunggah',
                    ['company_id' => $companyId, 'surat_tugas_visitasi_id' => $locked->getKey()],
                    'Dokumentasi visitasi diunggah',
                );

                return $documentation;
            });
        } catch (\Throwable $exception) {
            $this->files->delete($path);
            throw $exception;
        }
    }
}
