<?php

use App\Models\PengajuanPkl;
use App\Models\SiswaProfile;
use App\Models\User;

test('non-kaprog gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('kaprog.riwayat-verifikasi.index'))->assertForbidden();
});

test('index hanya menampilkan pengajuan terverifikasi jurusan sendiri', function () {
    $kaprog = kaprogUser();
    $kk = $kaprog->guruProfile->kompetensiKeahlian;
    $profileSendiri = SiswaProfile::factory()->create([
        'user_id' => User::factory()->create()->id,
        'kompetensi_keahlian_id' => $kk->id,
    ]);
    $profileLain = SiswaProfile::factory()->create();

    PengajuanPkl::factory()->create(['siswa_id' => $profileSendiri->id, 'status' => 'disetujui']);
    PengajuanPkl::factory()->create(['siswa_id' => $profileSendiri->id, 'status' => 'ditolak']);
    PengajuanPkl::factory()->create(['siswa_id' => $profileLain->id, 'status' => 'disetujui']);
    PengajuanPkl::factory()->create(['siswa_id' => $profileSendiri->id, 'status' => 'draft']);

    $this->actingAs($kaprog)
        ->get(route('kaprog.riwayat-verifikasi.index'))
        ->assertOk()
        ->assertSee($profileSendiri->user->name)
        ->assertDontSee($profileLain->user->name);
});
