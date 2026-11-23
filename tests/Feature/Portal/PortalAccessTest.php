<?php

test('tamu diarahkan ke /login di setiap permukaan portal', function (string $url) {
    $this->get($url)->assertRedirect('/login');
})->with(['/siswa', '/siswa/bimbingan', '/siswa/jadwal-sidang', '/walas', '/walas/kanban', '/kepsek']);

test('setiap peran membuka portalnya sendiri', function (string $role, string $url) {
    $user = pklUser([$role]);

    $this->actingAs($user)->withSession(['active_role' => $role]);

    $this->get($url)->assertOk();
})->with([
    ['siswa', '/siswa'],
    ['siswa', '/siswa/bimbingan'],
    ['siswa', '/siswa/jadwal-sidang'],
    ['walas', '/walas'],
    ['walas', '/walas/kanban'],
    ['kepsek', '/kepsek'],
]);

test('portal peran lain ditolak 403', function () {
    $siswa = pklUser(['siswa']);

    $this->actingAs($siswa)->withSession(['active_role' => 'siswa']);

    $this->get('/walas')->assertForbidden();
    $this->get('/kepsek')->assertForbidden();
});

test('peran aktif yang berbeda dengan rute portal ditolak 403', function () {
    $user = pklUser(['siswa', 'walas']);

    $this->actingAs($user)->withSession(['active_role' => 'walas']);

    $this->get('/siswa')->assertForbidden();
    $this->get('/walas')->assertOk();
});
