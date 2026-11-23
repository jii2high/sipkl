<?php

use Filament\Facades\Filament;

/**
 * UAT otomatis: setiap peran membuka halaman intinya dan ditolak di halaman
 * peran lain. Bagian yang tidak bisa diotomatiskan (360px/1280px, terang/gelap,
 * kontras, target sentuh) tetap checklist manual di `docs/GO-LIVE.md`.
 */
dataset('halaman per peran', [
    'siswa' => [
        'app', 'siswa',
        ['/app', '/siswa', '/siswa/bimbingan', '/siswa/jadwal-sidang', '/app/pengaturan-notifikasi'],
        ['/kaprog/antrean-pengajuan', '/kaprog/form-builder', '/app/kanban-kelas', '/kaprog/monitoring', '/guru/bimbingan-siswa'],
    ],
    'walas' => [
        'app', 'walas',
        ['/app', '/app/kanban-kelas', '/app/pengaturan-notifikasi'],
        ['/kaprog/monitoring', '/kaprog/form-builder', '/kaprog/plotting-guru', '/guru/visitasi'],
    ],
    'guru_pembimbing' => [
        'guru', 'guru_pembimbing',
        ['/guru', '/guru/bimbingan-siswa', '/guru/penilaian-industri', '/guru/visitasi', '/app/pengaturan-notifikasi'],
        ['/kaprog/antrean-pengajuan', '/app/kanban-kelas', '/kaprog/monitoring', '/kaprog/pengesahan-laporan'],
    ],
    'kaprog' => [
        'app', 'kaprog',
        ['/app', '/kaprog/antrean-pengajuan', '/kaprog/form-builder', '/kaprog/plotting-guru', '/kaprog/visitasi', '/kaprog/jadwal-sidang', '/kaprog/pengesahan-laporan', '/kaprog/penilaian-sidang', '/kaprog/monitoring', '/app/pengaturan-notifikasi'],
        ['/app/kanban-kelas', '/guru/penilaian-industri'],
    ],
    'kepsek' => [
        'app', 'kepsek',
        ['/app', '/app/pengaturan-notifikasi'],
        ['/app/kanban-kelas', '/kaprog/monitoring'],
    ],
]);

test('peran membuka halaman intinya dan ditolak di halaman peran lain', function (string $panel, string $role, array $allowed, array $denied) {
    Filament::setCurrentPanel(Filament::getPanel($panel));

    $user = pklUser([$role]);
    $this->actingAs($user)->withSession(['active_role' => $role]);

    foreach ($allowed as $url) {
        expect($this->get($url)->getStatusCode())->toBe(200, "GET $url seharusnya 200");
    }

    foreach ($denied as $url) {
        expect($this->get($url)->getStatusCode())->toBe(403, "GET $url seharusnya 403");
    }
})->with('halaman per peran');

test('hubin membuka halaman panel dan peran lain ditolak', function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $hubin = pklUser(['hubin']);
    $this->actingAs($hubin)->withSession(['active_role' => 'hubin']);

    foreach ([
        '/hubin',
        '/hubin/academic-periods',
        '/hubin/companies',
        '/hubin/users',
        '/hubin/signature-assets',
        '/hubin/faqs',
        '/hubin/faq-categories',
        '/hubin/ekspor-akreditasi',
        '/hubin/monitoring',
    ] as $url) {
        $this->get($url)->assertOk();
    }

    $guru = pklUser(['guru_pembimbing']);
    $this->actingAs($guru)->withSession(['active_role' => 'guru_pembimbing']);

    $this->get('/hubin')->assertForbidden();
    $this->get('/hubin/ekspor-akreditasi')->assertForbidden();
});
