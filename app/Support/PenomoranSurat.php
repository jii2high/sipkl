<?php

namespace App\Support;

use App\Models\SuratPengantar;
use App\Models\SuratTugasMonitoring;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Helper untuk generate nomor surat secara sequential dengan DB transaction lock.
 * Format (BR18): {noUrut}/421.5/SMKN 1-Cadisdik.Wil.{noWilayah}/{bulanRomawi}/{tahun}
 */
class PenomoranSurat
{
    private const NO_WILAYAH = 'II';

    /**
     * Generate nomor surat pengantar berikutnya dalam transaction + lock.
     * Dipanggil hanya di dalam DB::transaction().
     */
    public static function suratPengantar(int $periodeId): string
    {
        return self::format(self::urutBerikutnya(SuratPengantar::where('periode_id', $periodeId)));
    }

    /**
     * Generate nomor surat tugas monitoring berikutnya (BR18) — urutan terpisah dari surat pengantar.
     * Dipanggil hanya di dalam DB::transaction().
     */
    public static function suratTugas(int $periodeId): string
    {
        return self::format(self::urutBerikutnya(SuratTugasMonitoring::where('periode_id', $periodeId)));
    }

    /**
     * @template T of Model
     *
     * @param  Builder<T>  $query
     */
    private static function urutBerikutnya($query): int
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }

        return $query->count() + 1;
    }

    private static function format(int $urut): string
    {
        $bulanRomawi = self::toRomawi(now()->month);
        $tahun = now()->year;
        $wilayah = self::NO_WILAYAH;

        return "{$urut}/421.5/SMKN 1-Cadisdik.Wil.{$wilayah}/{$bulanRomawi}/{$tahun}";
    }

    private static function toRomawi(int $bulan): string
    {
        $romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $romawi[$bulan - 1];
    }
}
