<?php

use Filament\Facades\Filament;

/**
 * UAT otomatis (R6.1): matriks akses peran × permukaan. Tiap peran membuka
 * permukaannya sendiri (200), ditolak 403 di lima permukaan lain, dan tamu
 * diarahkan ke `/login`. Bagian yang tidak bisa diotomatiskan (360px/1280px,
 * terang/gelap, kontras, target sentuh) tetap checklist manual di
 * `docs/GO-LIVE.md`.
 */
$semuaPermukaan = ['/hubin', '/kaprog', '/guru', '/siswa', '/walas', '/kepsek'];

dataset('halaman per peran', [
    // peran => [panel Filament, peran aktif, permukaan, halaman yang juga boleh dibuka]
    'hubin' => ['hubin', 'hubin', '/hubin', [
        '/hubin/academic-periods',
        '/hubin/companies',
        '/hubin/users',
        '/hubin/signature-assets',
        '/hubin/faqs',
        '/hubin/faq-categories',
        '/hubin/ekspor-akreditasi',
        '/hubin/monitoring',
        '/notifikasi',
    ]],
    'siswa' => [null, 'siswa', '/siswa', [
        '/siswa/bimbingan',
        '/siswa/jadwal-sidang',
        '/notifikasi',
    ]],
    'walas' => [null, 'walas', '/walas', [
        '/walas/kanban',
        '/notifikasi',
    ]],
    'guru_pembimbing' => ['guru', 'guru_pembimbing', '/guru', [
        '/guru/bimbingan-siswa',
        '/guru/penilaian-industri',
        '/guru/visitasi',
        '/notifikasi',
    ]],
    'kaprog' => ['kaprog', 'kaprog', '/kaprog', [
        '/kaprog/antrean-pengajuan',
        '/kaprog/form-builder',
        '/kaprog/plotting-guru',
        '/kaprog/visitasi',
        '/kaprog/jadwal-sidang',
        '/kaprog/pengesahan-laporan',
        '/kaprog/penilaian-sidang',
        '/kaprog/monitoring',
        '/notifikasi',
    ]],
    'kepsek' => [null, 'kepsek', '/kepsek', [
        '/notifikasi',
    ]],
]);

test('tiap peran membuka permukaannya dan ditolak 403 di permukaan lain', function (?string $panel, string $role, string $permukaan, array $halaman) use ($semuaPermukaan) {
    if ($panel !== null) {
        Filament::setCurrentPanel(Filament::getPanel($panel));
    }

    $user = pklUser([$role]);
    $this->actingAs($user)->withSession(['active_role' => $role]);

    expect($this->get($permukaan)->getStatusCode())->toBe(200, "GET $permukaan seharusnya 200");

    foreach ($halaman as $url) {
        expect($this->get($url)->getStatusCode())->toBe(200, "GET $url seharusnya 200");
    }

    foreach (array_diff($semuaPermukaan, [$permukaan]) as $url) {
        expect($this->get($url)->getStatusCode())->toBe(403, "GET $url seharusnya 403");
    }
})->with('halaman per peran');

test('tamu diarahkan ke /login di setiap permukaan', function (string $url) {
    $this->get($url)->assertRedirect('/login');
})->with($semuaPermukaan);
