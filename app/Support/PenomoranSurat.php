<?php

namespace App\Support;

use App\Models\SuratPengantar;
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
        // Hitung jumlah surat pengantar untuk periode ini (lock for update mencegah race condition)
        $query = SuratPengantar::where('periode_id', $periodeId);
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $query->lockForUpdate();
        }
        $urut = $query->count() + 1;

        return self::format($urut);
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
