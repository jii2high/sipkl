<?php

use App\Models\Perusahaan;
use App\Models\User;

test('guests redirected to login', function () {
    $this->get(route('hubin.perusahaan.index'))->assertRedirect(route('login'));
});

test('non-hubin gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('hubin.perusahaan.index'))
        ->assertForbidden();
});

test('index returns perusahaan list', function () {
    asHubin();
    Perusahaan::factory()->count(3)->create();
    $this->get(route('hubin.perusahaan.index'))->assertOk();
});

test('create perusahaan', function () {
    asHubin();
    $this->post(route('hubin.perusahaan.store'), [
        'nama' => 'PT Maju', 'alamat' => 'Jl. Contoh 123', 'telp' => '021123456', 'email' => 'info@ptmaju.id', 'kuota_saran' => 6,
    ])->assertRedirect();
    $this->assertDatabaseHas('perusahaans', ['nama' => 'PT Maju', 'email' => 'info@ptmaju.id']);
});

test('create perusahaan validation', function () {
    asHubin();
    $this->post(route('hubin.perusahaan.store'), ['nama' => ''])->assertSessionHasErrors(['nama']);
});

test('update perusahaan', function () {
    asHubin();
    $p = Perusahaan::factory()->create(['nama' => 'PT Lama']);
    $this->put(route('hubin.perusahaan.update', $p), ['nama' => 'PT Baru'])->assertRedirect();
    $this->assertDatabaseHas('perusahaans', ['nama' => 'PT Baru']);
});

test('delete perusahaan', function () {
    asHubin();
    $p = Perusahaan::factory()->create();
    $this->delete(route('hubin.perusahaan.destroy', $p))->assertRedirect();
    $this->assertDatabaseMissing('perusahaans', ['id' => $p->id]);
});
