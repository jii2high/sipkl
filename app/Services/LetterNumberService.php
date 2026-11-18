<?php

namespace App\Services;

use App\Models\NomorSuratCounter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Counter nomor surat per (tahun ajaran, jenis) — BR-F12-1, TRD.md §8 & §11.
 * Baris counter diambil dengan `FOR UPDATE` di dalam transaksi pembuatan dokumen,
 * jadi rollback tidak menyisakan nomor terpakai dan dua penerbitan bersamaan
 * menghasilkan nomor berurutan tanpa duplikat. Format string disusun di sini:
 * [kode]/[urut 4 digit]/[suffix] (selisih dari contoh fisik dicatat di TASKS.md).
 */
final class LetterNumberService
{
    public const JENIS_SURAT_PENGANTAR = 'surat_pengantar';

    public const JENIS_SURAT_TUGAS_PEMBIMBING = 'surat_tugas_pembimbing';

    public const JENIS_SURAT_TUGAS_VISITASI = 'surat_tugas_visitasi';

    /**
     * Ambil nomor berikutnya untuk (tahun ajaran, jenis) di dalam transaksi
     * berjalan. Pengambilan dan pemakaian nomor berada di transaksi yang sama,
     * sehingga kegagalan setelah pengambilan membatalkan kenaikan counter.
     *
     * @return array{nomor_urut: int, nomor_surat: string}
     */
    public function allocate(string $tahunAjaran, string $jenisSurat): array
    {
        $this->assertJenis($jenisSurat);

        return DB::transaction(function () use ($tahunAjaran, $jenisSurat): array {
            // Baris counter dibuat sekali; `insertOrIgnore` aman untuk pemakaian
            // pertama yang bersamaan karena UNIQUE (tahun_ajaran, jenis_surat).
            DB::table('nomor_surat_counters')->insertOrIgnore([
                'tahun_ajaran' => $tahunAjaran,
                'jenis_surat' => $jenisSurat,
                'last_number' => 0,
            ]);

            $counter = NomorSuratCounter::query()
                ->where('tahun_ajaran', $tahunAjaran)
                ->where('jenis_surat', $jenisSurat)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                throw new RuntimeException('Counter nomor surat gagal dibuat.');
            }

            $nomorUrut = $counter->last_number + 1;
            $counter->update(['last_number' => $nomorUrut]);

            return [
                'nomor_urut' => $nomorUrut,
                'nomor_surat' => $this->format($jenisSurat, $nomorUrut),
            ];
        });
    }

    /** Susun string nomor surat (tanpa menyentuh counter). */
    public function format(string $jenisSurat, int $nomorUrut): string
    {
        $this->assertJenis($jenisSurat);

        return sprintf(
            '%s/%04d/%s',
            (string) config("pkl.document.nomor.kode.{$jenisSurat}"),
            $nomorUrut,
            (string) config('pkl.document.nomor.suffix'),
        );
    }

    private function assertJenis(string $jenisSurat): void
    {
        if (config("pkl.document.nomor.kode.{$jenisSurat}") === null) {
            throw new RuntimeException("Jenis surat tidak dikenal: {$jenisSurat}.");
        }
    }
}
