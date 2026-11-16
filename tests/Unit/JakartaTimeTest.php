<?php

use App\Services\JakartaTime;
use Carbon\CarbonImmutable;

it('mengembalikan 23:59:59.999999 Jakarta dalam UTC untuk nilai date-only', function () {
    $end = JakartaTime::endOfDay('2026-11-16');

    expect($end->timezoneName)->toBe('UTC')
        ->and($end->format('Y-m-d H:i:s.u'))->toBe('2026-11-16 16:59:59.999999');
});

it('menafsirkan input UTC sebagai tanggal Jakarta sebelum menghitung akhir hari', function () {
    // 2026-11-16 20:00 UTC = 2026-11-17 03:00 Jakarta.
    $end = JakartaTime::endOfDay(CarbonImmutable::parse('2026-11-16 20:00:00', 'UTC'));

    expect($end->format('Y-m-d H:i:s.u'))->toBe('2026-11-17 16:59:59.999999');
});

it('menghitung batas akhir hari secara inklusif sampai detik terakhir Jakarta', function () {
    $end = JakartaTime::endOfDay('2026-11-16');

    $masihHariItu = CarbonImmutable::parse('2026-11-16 23:30:00', 'Asia/Jakarta');
    $hariBerikutnya = CarbonImmutable::parse('2026-11-17 00:00:00', 'Asia/Jakarta');

    expect($masihHariItu->lessThanOrEqualTo($end))->toBeTrue()
        ->and($hariBerikutnya->greaterThan($end))->toBeTrue();
});

it('mengembalikan awal hari Jakarta dan tanggal hari ini di zona Jakarta', function () {
    $today = JakartaTime::today();

    expect($today->timezoneName)->toBe('Asia/Jakarta')
        ->and($today->format('H:i:s'))->toBe('00:00:00')
        ->and($today->toDateString())->toBe(CarbonImmutable::now('Asia/Jakarta')->toDateString());
});

it('mengubah waktu UTC ke tampilan Jakarta', function () {
    $display = JakartaTime::toDisplay('2026-11-16 16:59:59');

    expect($display->timezoneName)->toBe('Asia/Jakarta')
        ->and($display->format('Y-m-d H:i:s'))->toBe('2026-11-16 23:59:59');
});

it('menghitung hari kerja tanpa menghitung tanggal akhir dan tanpa libur nasional', function () {
    // 2026-11-16 Senin, 2026-11-20 Jumat.
    expect(JakartaTime::businessDaysBetween('2026-11-16', '2026-11-20'))->toBe(4);

    // Jumat → Senin: hanya Jumat yang dihitung, akhir pekan dilewati.
    expect(JakartaTime::businessDaysBetween('2026-11-13', '2026-11-16'))->toBe(1);

    // Sabtu → Senin: tidak ada hari kerja di antaranya.
    expect(JakartaTime::businessDaysBetween('2026-11-14', '2026-11-16'))->toBe(0);

    // Kamis → Selasa: Kamis, Jumat, Senin.
    expect(JakartaTime::businessDaysBetween('2026-11-12', '2026-11-17'))->toBe(3);
});

it('mengembalikan nol untuk rentang kosong atau terbalik', function () {
    expect(JakartaTime::businessDaysBetween('2026-11-16', '2026-11-16'))->toBe(0)
        ->and(JakartaTime::businessDaysBetween('2026-11-20', '2026-11-16'))->toBe(0);
});

it('memakai tanggal Jakarta saat menghitung hari kerja dari timestamp UTC', function () {
    // 2026-11-15 18:00 UTC = 2026-11-16 01:00 Jakarta (Senin), sampai 2026-11-18 01:00 Jakarta (Rabu).
    $start = CarbonImmutable::parse('2026-11-15 18:00:00', 'UTC');
    $end = CarbonImmutable::parse('2026-11-17 18:00:00', 'UTC');

    expect(JakartaTime::businessDaysBetween($start, $end))->toBe(2);
});
