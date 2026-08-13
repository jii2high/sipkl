<?php

use App\Models\PengajuanPkl;
use App\Models\PenugasanPembimbing;
use App\Models\PeriodePkl;
use App\Models\SiswaProfile;
use App\Models\SuratTugasMonitoring;
use App\Models\User;
use App\Notifications\SuratTugasTerbit;
use Illuminate\Support\Facades\Notification;

test('non-kaprog gets 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('kaprog.monitoring.index'))->assertForbidden();
});

test('index menampilkan draft scoped sendiri dan surat tugas terbit', function () {
    $kaprog = kaprogUser();
    $kkId = $kaprog->guruProfile->kompetensi_keahlian_id;
    SuratTugasMonitoring::factory()->create(['status' => 'draft', 'lingkup' => 'scoped', 'kompetensi_keahlian_id' => $kkId]);
    SuratTugasMonitoring::factory()->create(['status' => 'draft', 'lingkup' => 'scoped']);
    SuratTugasMonitoring::factory()->create(['status' => 'aktif', 'lingkup' => 'global']);

    $this->actingAs($kaprog)
        ->get(route('kaprog.monitoring.index'))
        ->assertOk();
});

test('kaprog membuat draft surat tugas scoped', function () {
    $kaprog = kaprogUser();
    $periode = PeriodePkl::factory()->create(['status_aktif' => true]);

    $this->actingAs($kaprog)
        ->post(route('kaprog.monitoring.store'), [
            'periode_id' => $periode->id,
            'berlaku_hingga' => now()->addMonth()->toDateString(),
        ])->assertRedirect();

    $this->assertDatabaseHas('surat_tugas_monitoring', [
        'status' => 'draft',
        'lingkup' => 'scoped',
        'kompetensi_keahlian_id' => $kaprog->guruProfile->kompetensi_keahlian_id,
    ]);
});

test('kaprog menerbitkan draft: status aktif + nomor otomatis + notif pembimbing', function () {
    Notification::fake();
    $kaprog = kaprogUser();
    $pembimbing = guruPembimbingUser();
    $periode = PeriodePkl::factory()->create(['status_aktif' => true]);
    $siswa = SiswaProfile::factory()->create([
        'user_id' => User::factory()->create()->id,
        'kompetensi_keahlian_id' => $kaprog->guruProfile->kompetensi_keahlian_id,
    ]);
    $pengajuan = PengajuanPkl::factory()->create([
        'siswa_id' => $siswa->id,
        'periode_id' => $periode->id,
    ]);
    PenugasanPembimbing::factory()->create([
        'pengajuan_id' => $pengajuan->id,
        'guru_pembimbing_id' => $pembimbing->id,
        'status' => 'aktif',
    ]);
    $surat = SuratTugasMonitoring::factory()->create([
        'status' => 'draft',
        'lingkup' => 'scoped',
        'kompetensi_keahlian_id' => $kaprog->guruProfile->kompetensi_keahlian_id,
        'periode_id' => $periode->id,
    ]);

    $this->actingAs($kaprog)
        ->post(route('kaprog.monitoring.terbit', $surat))
        ->assertRedirect();

    $this->assertDatabaseHas('surat_tugas_monitoring', [
        'id' => $surat->id,
        'status' => 'aktif',
        'diterbitkan_by' => $kaprog->id,
    ]);
    expect($surat->fresh()->nomor_surat)->not->toBeNull();
    Notification::assertSentTo($pembimbing, SuratTugasTerbit::class);
});

test('kaprog hanya bisa menghapus draft miliknya', function () {
    $kaprog = kaprogUser();
    $kkId = $kaprog->guruProfile->kompetensi_keahlian_id;
    $milik = SuratTugasMonitoring::factory()->create(['status' => 'draft', 'lingkup' => 'scoped', 'kompetensi_keahlian_id' => $kkId]);
    $lain = SuratTugasMonitoring::factory()->create(['status' => 'draft', 'lingkup' => 'scoped']);
    $terbit = SuratTugasMonitoring::factory()->create(['status' => 'aktif', 'lingkup' => 'scoped', 'kompetensi_keahlian_id' => $kkId]);

    $this->actingAs($kaprog)
        ->delete(route('kaprog.monitoring.destroy', $milik))
        ->assertRedirect();
    $this->assertDatabaseMissing('surat_tugas_monitoring', ['id' => $milik->id]);

    $this->actingAs($kaprog)
        ->delete(route('kaprog.monitoring.destroy', $lain))
        ->assertForbidden();
    $this->actingAs($kaprog)
        ->delete(route('kaprog.monitoring.destroy', $terbit))
        ->assertForbidden();
});
