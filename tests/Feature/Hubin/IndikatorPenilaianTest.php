<?php

use App\Models\IndikatorPenilaian;
use App\Models\SectionPenilaian;
use App\Models\User;

test('guests redirected to login', function () {
    $this->get(route('hubin.indikator-penilaian.index'))->assertRedirect(route('login'));
});

test('non-hubin gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('hubin.indikator-penilaian.index'))->assertForbidden();
});

test('index returns indikator list', function () {
    asHubin();
    IndikatorPenilaian::factory()->count(3)->create();
    $this->get(route('hubin.indikator-penilaian.index'))->assertOk();
});

test('create indikator penilaian', function () {
    asHubin();
    $this->post(route('hubin.indikator-penilaian.store'), [
        'nomor_urut' => 1, 'nama' => 'Kedisiplinan',
    ])->assertRedirect();
    $this->assertDatabaseHas('indikator_penilaians', ['nama' => 'Kedisiplinan']);
});

test('create indikator penilaian validation', function () {
    asHubin();
    $this->post(route('hubin.indikator-penilaian.store'), ['nama' => ''])->assertSessionHasErrors(['nama']);
});

test('create section under indikator', function () {
    asHubin();
    $i = IndikatorPenilaian::factory()->create();
    $this->post(route('hubin.indikator-penilaian.section.store', $i), [
        'nomor_urut' => 1, 'nama' => 'Datang tepat waktu',
    ])->assertRedirect();
    $this->assertDatabaseHas('section_penilaians', ['nama' => 'Datang tepat waktu']);
});

test('update section', function () {
    asHubin();
    $s = SectionPenilaian::factory()->create(['nama' => 'Lama']);
    $this->put(route('hubin.indikator-penilaian.section.update', $s), [
        'nomor_urut' => 1,
        'nama' => 'Baru',
    ])->assertRedirect();
    $this->assertDatabaseHas('section_penilaians', ['nama' => 'Baru']);
});

test('delete section', function () {
    asHubin();
    $s = SectionPenilaian::factory()->create();
    $this->delete(route('hubin.indikator-penilaian.section.destroy', $s))->assertRedirect();
    $this->assertSoftDeleted('section_penilaians', ['id' => $s->id]);
});
