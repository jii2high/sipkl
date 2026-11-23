<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Waktu operasional PKL: DB menyimpan UTC, aturan dan tampilan memakai Asia/Jakarta.
 * Tanggal tanpa jam bersifat inklusif sampai 23:59:59 Jakarta (TRD.md §15).
 */
final class JakartaTime
{
    public const TZ = 'Asia/Jakarta';

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TZ);
    }

    public static function today(): CarbonImmutable
    {
        return self::now()->startOfDay();
    }

    /** Awal hari kalender Jakarta, dikembalikan sebagai UTC untuk query DB. */
    public static function startOfDay(DateTimeInterface|string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, self::TZ)->startOfDay()->utc();
    }

    /** Akhir hari kalender Jakarta (23:59:59), dikembalikan sebagai UTC. */
    public static function endOfDay(DateTimeInterface|string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, self::TZ)->endOfDay()->utc();
    }

    /** Kunci "tanggal Jakarta" untuk dedup job harian. */
    public static function dateKey(DateTimeInterface|string|null $moment = null): string
    {
        return self::resolve($moment)->setTimezone(self::TZ)->toDateString();
    }

    /** Nama bulan Bahasa Indonesia; dipakai surat PDF tanpa bergantung locale. */
    private const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /** Tanggal panjang Bahasa Indonesia, mis. "21 April 2026" (zona Asia/Jakarta). */
    public static function humanDate(DateTimeInterface|string|null $moment = null): string
    {
        $date = self::resolve($moment)->setTimezone(self::TZ);

        return $date->day.' '.self::MONTHS[$date->month].' '.$date->year;
    }

    /** Bulan dan tahun Jakarta, mis. "Juli 2026" (untuk rentang masa PKL). */
    public static function monthYear(DateTimeInterface|string|null $moment = null): string
    {
        $date = self::resolve($moment)->setTimezone(self::TZ);

        return self::MONTHS[$date->month].' '.$date->year;
    }

    /** SLA dihitung Senin-Jumat; libur nasional tidak dikecualikan (non-goal PRD). */
    public static function isWeekday(DateTimeInterface|string|null $moment = null): bool
    {
        return self::resolve($moment)->isWeekday();
    }

    /** Jumlah hari kerja (Senin-Jumat) dari `$from` sampai `$to`, keduanya inklusif. */
    public static function workingDaysBetween(DateTimeInterface|string $from, DateTimeInterface|string $to): int
    {
        // Normalisasi ke Jakarta dulu: DateTimeInterface dari DB datang sebagai UTC.
        $start = self::resolve($from)->setTimezone(self::TZ)->startOfDay();
        $end = self::resolve($to)->setTimezone(self::TZ)->startOfDay();

        if ($end->lessThan($start)) {
            return 0;
        }

        $days = 0;

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            if ($day->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }

    /** Hari kerja yang sudah berlalu sejak `$since`; hari itu sendiri dihitung 0. */
    public static function workingDaysElapsed(DateTimeInterface|string $since, DateTimeInterface|string|null $until = null): int
    {
        return max(0, self::workingDaysBetween($since, $until ?? self::now()) - 1);
    }

    private static function resolve(DateTimeInterface|string|null $moment): CarbonImmutable
    {
        if ($moment === null) {
            return self::now();
        }

        return $moment instanceof DateTimeInterface
            ? CarbonImmutable::instance($moment)
            : CarbonImmutable::parse($moment, self::TZ);
    }
}
