<?php

use Filament\Facades\Filament;

/**
 * UAT otomatis: setiap peran membuka halaman intinya dan ditolak di halaman
 * peran lain. Bagian yang tidak bisa diotomatiskan (360px/1280px, terang/gelap,
 * kontras, target sentuh) tetap checklist manual di `docs/GO-LIVE.md`.
 */
dataset('halaman per peran', [
    'siswa' => [
        null, 'siswa',
        ['/siswa', '/siswa/bimbingan', '/siswa/jadwal-sidang', '/notifikasi'],
        ['/kaprog/antrean-pengajuan', '/kaprog/form-builder', '/walas/kanban', '/kaprog/monitoring', '/guru/bimbingan-siswa'],
    ],
    'walas' => [
        null, 'walas',
        ['/walas', '/walas/kanban', '/notifikasi'],
        ['/kaprog/monitoring', '/kaprog/form-builder', '/kaprog/plotting-guru', '/guru/visitasi'],
    ],
    'guru_pembimbing' => [
        'guru', 'guru_pembimbing',
        ['/guru', '/guru/bimbingan-siswa', '/guru/penilaian-industri', '/guru/visitasi', '/notifikasi'],
        ['/kaprog/antrean-pengajuan', '/walas/kanban', '/kaprog/monitoring', '/kaprog/pengesahan-laporan'],
    ],
    'kaprog' => [
        'kaprog', 'kaprog',
        ['/kaprog', '/kaprog/antrean-pengajuan', '/kaprog/form-builder', '/kaprog/plotting-guru', '/kaprog/visitasi', '/kaprog/jadwal-sidang', '/kaprog/pengesahan-laporan', '/kaprog/penilaian-sidang', '/kaprog/monitoring', '/notifikasi'],
        ['/walas/kanban', '/guru/penilaian-industri'],
    ],
    'kepsek' => [
        null, 'kepsek',
        ['/kepsek', '/notifikasi'],
        ['/walas/kanban', '/kaprog/monitoring'],
    ],
]);

test('peran membuka halaman intinya dan ditolak di halaman peran lain', function (?string $panel, string $role, array $allowed, array $denied) {
    if ($panel !== null) {
        Filament::setCurrentPanel(Filament::getPanel($panel));
    }

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
