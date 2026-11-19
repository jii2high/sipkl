<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanPklStatus;
use App\Enums\PengajuanStatus;
use App\Exceptions\InvalidTransition;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Services\DocumentService;

/**
 * Terbitkan surat pengantar per company group (F3 alur 5, TRD.md §5.2 & §11).
 * Nomor + PDF + QR dibuat `DocumentService`; setelah surat bernomor, seluruh
 * siswa di group berpindah ke `surat_pengantar_terbit` dan group ke
 * `surat_terbit` dalam transaksi yang sama.
 *
 * Idempoten: group yang sudah pernah diterbitkan tidak mengambil nomor baru dan
 * siswa yang sudah berpindah tidak diaudit dua kali.
 */
final class IssueSuratPengantar extends PengajuanAction
{
    public function __construct(private readonly DocumentService $documents) {}

    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanPkl $group */
        /** @var User $hubin */
        [$group, $hubin] = $arguments;

        if ($group->status !== PengajuanPklStatus::Terbentuk->value) {
            return 0;
        }

        $this->authorize('issueLetter', $group, $hubin);

        return $this->transaction(function () use ($group, $hubin): int {
            $locked = PengajuanPkl::query()->whereKey($group->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== PengajuanPklStatus::Terbentuk->value) {
                return 0;
            }

            $tahunAjaran = $locked->academicPeriod?->tahun_ajaran ?? $locked->tahun_ajaran;

            if (! is_string($tahunAjaran) || $tahunAjaran === '') {
                throw new InvalidTransition('Tahun ajaran company group belum terisi.');
            }

            $this->documents->issue($locked, DocumentService::JENIS_PENGANTAR, $tahunAjaran, $hubin);

            $this->transitionStatus(
                $locked,
                PengajuanPklStatus::SuratTerbit->value,
                PengajuanPklStatus::Terbentuk->value,
            );

            $this->audit(
                $locked,
                'surat_pengantar_terbit',
                PengajuanPklStatus::Terbentuk->value,
                PengajuanPklStatus::SuratTerbit->value,
                ['nomor_surat' => $locked->nomor_surat],
            );

            $affected = 0;

            $students = PengajuanSiswa::query()
                ->where('pengajuan_pkl_id', $locked->getKey())
                ->where('status', PengajuanStatus::MenungguSuratPengantar->value)
                ->get();

            foreach ($students as $student) {
                $changed = $this->transitionStatus(
                    $student,
                    PengajuanStatus::SuratPengantarTerbit->value,
                    PengajuanStatus::MenungguSuratPengantar->value,
                );

                if ($changed === 1) {
                    $this->audit(
                        $student,
                        'surat_pengantar_diterima',
                        PengajuanStatus::MenungguSuratPengantar->value,
                        PengajuanStatus::SuratPengantarTerbit->value,
                        ['nomor_surat' => $locked->nomor_surat],
                    );

                    $affected++;
                }
            }

            return $affected;
        });
    }
}
