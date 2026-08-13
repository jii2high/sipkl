<?php

use App\Models\KompetensiKeahlian;
use App\Models\SiswaProfile;
use App\Models\User;

test('guests redirected to login', function () {
    $this->get(route('hubin.kompetensi-keahlian.index'))->assertRedirect(route('login'));
});

test('non-hubin gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('hubin.kompetensi-keahlian.index'))->assertForbidden();
});

test('index returns kompetensi keahlian list', function () {
    asHubin();
    $this->get(route('hubin.kompetensi-keahlian.index'))->assertOk();
});

test('create kompetensi keahlian', function () {
    asHubin();
    $this->post(route('hubin.kompetensi-keahlian.store'), [
        'nama' => 'RPL', 'acronym' => 'RPL',
    ])->assertRedirect();
    $this->assertDatabaseHas('kompetensi_keahlian', ['nama' => 'RPL']);
});

test('create kompetensi keahlian validation', function () {
    asHubin();
    $this->post(route('hubin.kompetensi-keahlian.store'), ['nama' => ''])->assertSessionHasErrors(['nama']);
});

test('update kompetensi keahlian', function () {
    asHubin();
    $kk = KompetensiKeahlian::factory()->create(['nama' => 'RPL']);
    $this->put(route('hubin.kompetensi-keahlian.update', $kk), ['nama' => 'TKJ'])->assertRedirect();
    $this->assertDatabaseHas('kompetensi_keahlian', ['nama' => 'TKJ']);
});

test('delete kompetensi keahlian', function () {
    asHubin();
    $kk = KompetensiKeahlian::factory()->create();
    $this->delete(route('hubin.kompetensi-keahlian.destroy', $kk))->assertRedirect();
    $this->assertSoftDeleted('kompetensi_keahlian', ['id' => $kk->id]);
});

test('delete blocked kompetensi keahlian flashes error toast', function () {
    asHubin();
    $kk = KompetensiKeahlian::factory()->create();
    SiswaProfile::factory()->create(['kompetensi_keahlian_id' => $kk->id]);

    $this->delete(route('hubin.kompetensi-keahlian.destroy', $kk))
        ->assertRedirect(route('hubin.kompetensi-keahlian.index'))
        ->assertSessionHas('inertia.flash_data', [
            'toast' => [
                'type' => 'error',
                'message' => 'Jurusan tidak dapat dihapus karena masih memiliki siswa yang terkait.',
            ],
        ]);
});
