<?php

use App\Models\Perusahaan;
use App\Models\User;

test('non-hubin gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('hubin.recycle-bin.index'))->assertForbidden();
});

test('index menampilkan akun dan master data terhapus', function () {
    asHubin();
    $siswa = siswaUser();
    $siswa->delete();
    $p = Perusahaan::factory()->create();
    $p->delete();

    $this->get(route('hubin.recycle-bin.index'))
        ->assertOk()
        ->assertSee($siswa->name)
        ->assertSee($p->nama);
});

test('restore akun dan perusahaan', function () {
    asHubin();
    $siswa = siswaUser();
    $siswa->delete();
    $p = Perusahaan::factory()->create();
    $p->delete();

    $this->post(route('hubin.recycle-bin.restore'), ['type' => 'akun', 'id' => $siswa->id])->assertRedirect();
    $this->post(route('hubin.recycle-bin.restore'), ['type' => 'perusahaan', 'id' => $p->id])->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $siswa->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('perusahaans', ['id' => $p->id, 'deleted_at' => null]);
});

test('force delete menghapus permanen akun beserta profil', function () {
    asHubin();
    $siswa = siswaUser();
    $siswa->delete();

    $this->delete(route('hubin.recycle-bin.force-destroy'), ['type' => 'akun', 'id' => $siswa->id])->assertRedirect();

    $this->assertDatabaseMissing('users', ['id' => $siswa->id]);
    $this->assertDatabaseMissing('siswa_profiles', ['user_id' => $siswa->id]);
});

test('force delete menghapus permanen perusahaan', function () {
    asHubin();
    $p = Perusahaan::factory()->create();
    $p->delete();

    $this->delete(route('hubin.recycle-bin.force-destroy'), ['type' => 'perusahaan', 'id' => $p->id])->assertRedirect();

    $this->assertDatabaseMissing('perusahaans', ['id' => $p->id]);
});
