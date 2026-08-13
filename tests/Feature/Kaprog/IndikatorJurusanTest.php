<?php

use App\Models\GuruProfile;
use App\Models\IndikatorPenilaian;
use App\Models\SectionPenilaian;
use App\Models\User;

test('non-kaprog gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('kaprog.indikator-jurusan.index'))->assertForbidden();
});

test('kaprog tanpa kompetensi keahlian gets 403', function () {
    $user = User::factory()->create();
    $user->assignRole('kaprog');
    GuruProfile::factory()->create(['user_id' => $user->id, 'jabatan' => 'kaprog', 'kompetensi_keahlian_id' => null]);

    $this->actingAs($user)
        ->get(route('kaprog.indikator-jurusan.index'))->assertForbidden();
});

test('index hanya menampilkan indikator jurusan sendiri', function () {
    $kaprog = kaprogUser();
    $kk = $kaprog->guruProfile->kompetensiKeahlian;
    IndikatorPenilaian::factory()->create(['kompetensi_keahlian_id' => $kk->id, 'nama' => 'Milikku']);
    IndikatorPenilaian::factory()->create(['nama' => 'Jurusan Lain']);

    $this->actingAs($kaprog)
        ->get(route('kaprog.indikator-jurusan.index'))
        ->assertOk()
        ->assertSee('Milikku')
        ->assertDontSee('Jurusan Lain');
});

test('kaprog membuat indikator jurusan miliknya', function () {
    $kaprog = kaprogUser();

    $this->actingAs($kaprog)
        ->post(route('kaprog.indikator-jurusan.store'), [
            'nomor_urut' => 1,
            'nama' => 'Etos Kerja',
        ])->assertRedirect();

    $this->assertDatabaseHas('indikator_penilaians', [
        'nama' => 'Etos Kerja',
        'kompetensi_keahlian_id' => $kaprog->guruProfile->kompetensi_keahlian_id,
    ]);
});

test('kaprog menambah section dan sub-section', function () {
    $kaprog = kaprogUser();
    $i = IndikatorPenilaian::factory()->create([
        'kompetensi_keahlian_id' => $kaprog->guruProfile->kompetensi_keahlian_id,
    ]);

    $this->actingAs($kaprog)
        ->post(route('kaprog.indikator-jurusan.section.store', $i), [
            'nomor_urut' => 1,
            'nama' => 'Disiplin',
        ])->assertRedirect();

    $s = SectionPenilaian::where('nama', 'Disiplin')->first();
    expect($s)->not->toBeNull();

    $this->actingAs($kaprog)
        ->post(route('kaprog.indikator-jurusan.sub-section.store', $s), [
            'nomor_urut' => 1,
            'nama' => 'Tepat waktu',
        ])->assertRedirect();

    $this->assertDatabaseHas('sub_section_penilaians', ['nama' => 'Tepat waktu']);
});

test('kaprog tidak bisa mengubah indikator jurusan lain', function () {
    $kaprog = kaprogUser();
    $indikatorLain = IndikatorPenilaian::factory()->create();

    $this->actingAs($kaprog)
        ->put(route('kaprog.indikator-jurusan.update', $indikatorLain), [
            'nomor_urut' => 1,
            'nama' => 'X',
        ])->assertForbidden();

    $this->actingAs($kaprog)
        ->delete(route('kaprog.indikator-jurusan.destroy', $indikatorLain))
        ->assertForbidden();
});

test('reorder menggeser nomor urut indikator', function () {
    $kaprog = kaprogUser();
    $kkId = $kaprog->guruProfile->kompetensi_keahlian_id;
    $a = IndikatorPenilaian::factory()->create(['kompetensi_keahlian_id' => $kkId, 'nomor_urut' => 1]);
    $b = IndikatorPenilaian::factory()->create(['kompetensi_keahlian_id' => $kkId, 'nomor_urut' => 2]);

    $this->actingAs($kaprog)
        ->post(route('kaprog.indikator-jurusan.reorder'), [
            'level' => 'indikator',
            'itemId' => $a->id,
            'fromIndex' => 0,
            'toIndex' => 1,
        ])->assertRedirect();

    expect($a->fresh()->nomor_urut)->toBe(2);
    expect($b->fresh()->nomor_urut)->toBe(1);
});
