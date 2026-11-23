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
        ['/app', '/app/pengajuan-saya', '/app/bimbingan-saya', '/app/jadwal-sidang-saya', '/app/pengaturan-notifikasi'],
        ['/kaprog/antrean-pengajuan', '/app/kanban-kelas', '/app/monitoring', '/app/form-builder'],
    ],
    'walas' => [
        'app', 'walas',
        ['/app', '/app/kanban-kelas', '/app/pengaturan-notifikasi'],
        ['/app/monitoring', '/app/form-builder', '/app/plotting-guru'],
    ],
    'guru_pembimbing' => [
        'app', 'guru_pembimbing',
        ['/app', '/app/bimbingan-siswa', '/app/visitasi', '/app/penilaian-industri', '/app/pengaturan-notifikasi'],
        ['/kaprog/antrean-pengajuan', '/app/kanban-kelas', '/app/monitoring', '/app/pengesahan-laporan'],
    ],
    'kaprog' => [
        'app', 'kaprog',
        ['/app', '/kaprog/antrean-pengajuan', '/app/plotting-guru', '/app/form-builder', '/app/jadwal-sidang', '/app/pengesahan-laporan', '/app/penilaian-sidang', '/app/monitoring', '/app/pengaturan-notifikasi'],
        ['/app/kanban-kelas', '/app/penilaian-industri'],
    ],
    'kepsek' => [
        'app', 'kepsek',
        ['/app', '/app/pengesahan-laporan', '/app/pengaturan-notifikasi'],
        ['/app/kanban-kelas', '/app/monitoring'],
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
