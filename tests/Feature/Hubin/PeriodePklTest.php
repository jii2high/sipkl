<?php

use App\Models\PeriodePkl;
use App\Models\User;

test('guests redirected to login', function () {
    $this->get(route('hubin.periode-pkl.index'))->assertRedirect(route('login'));
});

test('non-hubin gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('hubin.periode-pkl.index'))->assertForbidden();
});

test('index returns periode pkl list', function () {
    asHubin();
    PeriodePkl::factory()->count(3)->create();
    $this->get(route('hubin.periode-pkl.index'))->assertOk();
});

test('create periode pkl', function () {
    asHubin();
    $this->post(route('hubin.periode-pkl.store'), [
        'nama' => 'PKL 2026', 'tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-06-30', 'status_aktif' => true,
    ])->assertRedirect();
    $this->assertDatabaseHas('periode_pkls', ['nama' => 'PKL 2026']);
});

test('create periode pkl validation', function () {
    asHubin();
    $this->post(route('hubin.periode-pkl.store'), ['nama' => ''])->assertSessionHasErrors(['nama']);
});

test('update periode pkl', function () {
    asHubin();
    $p = PeriodePkl::factory()->create(['nama' => 'PKL Lama']);
    $this->put(route('hubin.periode-pkl.update', $p), [
        'nama' => 'PKL Baru',
        'tanggal_mulai' => '2026-01-01',
        'tanggal_selesai' => '2026-06-30',
        'status_aktif' => false,
    ])->assertRedirect();
    $this->assertDatabaseHas('periode_pkls', ['nama' => 'PKL Baru']);
});

test('delete periode pkl', function () {
    asHubin();
    $p = PeriodePkl::factory()->create();
    $this->delete(route('hubin.periode-pkl.destroy', $p))->assertRedirect();
    $this->assertDatabaseMissing('periode_pkls', ['id' => $p->id]);
});
