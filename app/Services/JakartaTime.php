<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Satu-satunya sumber aturan waktu aplikasi (PRD §1.5 butir 9, TASKS M0.4).
 *
 * - Database menyimpan UTC.
 * - Operasional berjalan di Asia/Jakarta.
 * - Nilai date-only inklusif sampai 23:59:59.999999 Jakarta.
 * - Hitungan SLA memakai hari Senin–Jumat, libur nasional TIDAK dikecualikan (PRD §1.3 non-goal).
 */
final class JakartaTime
{
    public const string TIMEZONE = 'Asia/Jakarta';

    public const string STORAGE_TIMEZONE = 'UTC';

    /**
     * Tanggal hari ini menurut Jakarta, di awal hari (00:00:00 Jakarta).
     */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /**
     * Instant UTC untuk 23:59:59.999999 Jakarta pada tanggal yang diberikan.
     *
     * Dipakai untuk perbandingan inklusif pada kolom date-only.
     */
    public static function endOfDay(CarbonInterface|string $date): CarbonImmutable
    {
        return self::inJakarta($date)
            ->setTime(23, 59, 59, 999999)
            ->setTimezone(self::STORAGE_TIMEZONE);
    }

    /**
     * Instant UTC untuk 00:00:00 Jakarta pada tanggal yang diberikan.
     */
    public static function startOfDay(CarbonInterface|string $date): CarbonImmutable
    {
        return self::inJakarta($date)
            ->startOfDay()
            ->setTimezone(self::STORAGE_TIMEZONE);
    }

    /**
     * Ubah nilai UTC (atau string apa pun yang dapat diparse) ke representasi tampilan Jakarta.
     */
    public static function toDisplay(CarbonInterface|string $utc): CarbonImmutable
    {
        if (is_string($utc)) {
            $utc = CarbonImmutable::parse($utc, self::STORAGE_TIMEZONE);
        }

        return $utc->setTimezone(self::TIMEZONE);
    }

    /**
     * Jumlah hari kerja (Senin–Jumat) pada interval [start, end) menurut tanggal Jakarta.
     *
     * Semantik: tanggal `end` tidak dihitung; tanggal `start` dihitung bila hari kerja.
     * Contoh: Senin → Jumat = 4; Jumat → Senin = 1; Sabtu → Senin = 0; rentang terbalik = 0.
     */
    public static function businessDaysBetween(CarbonInterface|string $start, CarbonInterface|string $end): int
    {
        $from = self::inJakarta($start)->startOfDay();
        $to = self::inJakarta($end)->startOfDay();

        if ($from->greaterThanOrEqualTo($to)) {
            return 0;
        }

        $days = 0;

        for ($day = $from; $day->lessThan($to); $day = $day->addDay()) {
            if ($day->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }

    private static function inJakarta(CarbonInterface|string $value): CarbonImmutable
    {
        if (is_string($value)) {
            return CarbonImmutable::parse($value, self::TIMEZONE);
        }

        return CarbonImmutable::instance($value)->setTimezone(self::TIMEZONE);
    }
}
