<?php

use App\Models\SiswaProfile;
use App\Models\User;

test('non-hubin gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('hubin.kelola-siswa.index'))->assertForbidden();
});

test('index returns siswa list', function () {
    asHubin();
    SiswaProfile::factory()->count(3)->create();

    $this->get(route('hubin.kelola-siswa.index'))->assertOk();
});

test('destroy siswa soft deletes dan masuk recycle bin', function () {
    asHubin();
    $siswa = siswaUser();

    $this->delete(route('hubin.kelola-siswa.destroy', $siswa))->assertRedirect();

    $this->assertSoftDeleted('users', ['id' => $siswa->id]);
});

test('destroy non-siswa returns 422', function () {
    asHubin();
    $user = User::factory()->create();

    $this->delete(route('hubin.kelola-siswa.destroy', $user))->assertStatus(422);
});
