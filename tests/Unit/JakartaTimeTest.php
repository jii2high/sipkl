<?php

use App\Support\JakartaTime;
use Carbon\CarbonImmutable;

test('akhir hari Jakarta dipetakan ke 16:59:59 UTC', function () {
    expect(JakartaTime::endOfDay('2026-11-18')->toDateTimeString())->toBe('2026-11-18 16:59:59');
    expect(JakartaTime::startOfDay('2026-11-18')->toDateTimeString())->toBe('2026-11-17 17:00:00');
});

test('batas tanggal bersifat inklusif sampai 23:59:59 Jakarta', function () {
    $batas = JakartaTime::endOfDay('2026-11-18');

    expect(CarbonImmutable::parse('2026-11-18 16:59:59', 'UTC')->lessThanOrEqualTo($batas))->toBeTrue()
        ->and(CarbonImmutable::parse('2026-11-18 17:00:00', 'UTC')->lessThanOrEqualTo($batas))->toBeFalse();
});

test('hari kerja adalah Senin sampai Jumat', function () {
    expect(JakartaTime::isWeekday('2026-11-18'))->toBeTrue()
        ->and(JakartaTime::isWeekday('2026-11-21'))->toBeFalse()
        ->and(JakartaTime::isWeekday('2026-11-22'))->toBeFalse();
});

test('selisih hari kerja menghitung kedua ujung secara inklusif', function () {
    expect(JakartaTime::workingDaysBetween('2026-11-16', '2026-11-22'))->toBe(5)
        ->and(JakartaTime::workingDaysBetween('2026-11-21', '2026-11-15'))->toBe(0);
});

test('kunci tanggal Jakarta memakai batas tengah malam setempat', function () {
    expect(JakartaTime::dateKey(CarbonImmutable::parse('2026-11-18 16:00:00', 'UTC')))->toBe('2026-11-18')
        ->and(JakartaTime::dateKey(CarbonImmutable::parse('2026-11-18 17:00:00', 'UTC')))->toBe('2026-11-19');
});
