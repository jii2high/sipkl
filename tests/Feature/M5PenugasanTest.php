<?php

use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use App\Models\KunjunganMonitoring;
use App\Models\PengajuanPkl;
use App\Models\PenugasanPembimbing;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\SiswaProfile;
use App\Models\SuratTugasMonitoring;
use App\Models\User;
use App\Notifications\KunjunganBaru;
use App\Notifications\RequestSuratTugas;
use App\Notifications\SuratTugasTerbit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Setup minimal M5: kaprog + guru pembimbing + siswa satu jurusan + pengajuan disetujui.
 */
function siapkanPenugasan(): array
{
    $kk = KompetensiKeahlian::factory()->create();
    $periode = PeriodePkl::factory()->create(['status_aktif' => true]);

    $kaprog = User::factory()->create();
    $kaprog->assignRole('kaprog');
    GuruProfile::factory()->create([
        'user_id' => $kaprog->id,
        'jabatan' => 'kaprog',
        'kompetensi_keahlian_id' => $kk->id,
    ]);

    $pembimbing = User::factory()->create();
    $pembimbing->assignRole('guru_pembimbing');
    GuruProfile::factory()->create([
        'user_id' => $pembimbing->id,
        'jabatan' => 'guru_pembimbing',
        'kompetensi_keahlian_id' => $kk->id,
    ]);

    $siswa = User::factory()->create();
    $siswa->assignRole('siswa');
    $siswaProfile = SiswaProfile::factory()->create([
        'user_id' => $siswa->id,
        'kompetensi_keahlian_id' => $kk->id,
    ]);

    $perusahaan = Perusahaan::factory()->create();
    $pengajuan = PengajuanPkl::factory()->create([
        'siswa_id' => $siswaProfile->id,
        'perusahaan_id' => $perusahaan->id,
        'periode_id' => $periode->id,
        'status' => 'disetujui',
    ]);

    return compact('kk', 'periode', 'kaprog', 'pembimbing', 'siswa', 'perusahaan', 'pengajuan');
}

// ─── Assignment Guru Pembimbing (BR10) ────────────────────────

it('kaprog bisa assign pembimbing untuk siswa jurusannya sendiri', function () {
    $data = siapkanPenugasan();

    $this->actingAs($data['kaprog'])
        ->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
            'guru_pembimbing_id' => $data['pembimbing']->id,
        ])
        ->assertRedirect();

    $penugasan = PenugasanPembimbing::first();
    expect($penugasan)->not->toBeNull();
    expect($penugasan->guru_pembimbing_id)->toBe($data['pembimbing']->id);
    expect($penugasan->pengajuan_id)->toBe($data['pengajuan']->id);
    expect($penugasan->assigned_by)->toBe($data['kaprog']->id);
    expect($penugasan->status)->toBe('aktif');
});

it('kaprog ditolak assign pembimbing lintas jurusan', function () {
    $data = siapkanPenugasan();
    $kaprogLain = kaprogUser();

    $this->actingAs($kaprogLain)
        ->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
            'guru_pembimbing_id' => $data['pembimbing']->id,
        ])
        ->assertForbidden();

    expect(PenugasanPembimbing::count())->toBe(0);
});

it('self-assign kaprog memberinya permission kunjungan', function () {
    $data = siapkanPenugasan();

    $this->actingAs($data['kaprog'])
        ->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
            'guru_pembimbing_id' => $data['kaprog']->id,
        ])
        ->assertRedirect();

    expect($data['kaprog']->hasPermissionTo('kunjungan.isi'))->toBeTrue();
    expect($data['kaprog']->hasPermissionTo('kunjungan.lihat'))->toBeTrue();

    $penugasan = PenugasanPembimbing::first();
    expect($penugasan->guru_pembimbing_id)->toBe($data['kaprog']->id);
    expect($penugasan->assigned_by)->toBe($data['kaprog']->id);
});

it('penggantian pembimbing mengarsipkan assignment lama (BR30)', function () {
    $data = siapkanPenugasan();

    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['pembimbing']->id,
    ]);
    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['kaprog']->id,
    ]);

    expect(PenugasanPembimbing::count())->toBe(2);
    $lama = PenugasanPembimbing::where('guru_pembimbing_id', $data['pembimbing']->id)->first();
    expect($lama->status)->toBe('nonaktif');
    expect($lama->ended_at)->not->toBeNull();
    expect($data['pengajuan']->fresh()->penugasanAktif->guru_pembimbing_id)->toBe($data['kaprog']->id);
});

it('hanya kaprog yang bisa assign pembimbing', function () {
    $data = siapkanPenugasan();

    asHubin()
        ->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
            'guru_pembimbing_id' => $data['pembimbing']->id,
        ])
        ->assertForbidden();

    expect(PenugasanPembimbing::count())->toBe(0);
});

// ─── Surat Tugas Monitoring (BR22, BR23) ─────────────────────

it('hubin terbitkan surat tugas global: nomor, pdf tersimpan, notif ke pembimbing', function () {
    Storage::fake('local');
    Notification::fake();
    $data = siapkanPenugasan();
    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['pembimbing']->id,
    ]);

    asHubin()->post('/hubin/surat-tugas', [
        'periode_id' => $data['periode']->id,
        'lingkup' => 'global',
        'berlaku_hingga' => today()->addMonth()->toDateString(),
    ])->assertRedirect();

    $surat = SuratTugasMonitoring::first();
    expect($surat)->not->toBeNull();
    expect($surat->status)->toBe('aktif');
    expect($surat->nomor_surat)->not->toBeEmpty();
    expect($surat->file)->not->toBeNull();
    expect(Storage::exists($surat->file))->toBeTrue();

    Notification::assertSentTo($data['pembimbing'], SuratTugasTerbit::class);
});

it('surat tugas scoped mengirim notif ke kaprog peminta', function () {
    Storage::fake('local');
    Notification::fake();
    $data = siapkanPenugasan();

    asHubin()->post('/hubin/surat-tugas', [
        'periode_id' => $data['periode']->id,
        'lingkup' => 'scoped',
        'kompetensi_keahlian_id' => $data['kk']->id,
        'requested_by' => $data['kaprog']->id,
        'berlaku_hingga' => today()->addMonth()->toDateString(),
    ])->assertRedirect();

    Notification::assertSentTo($data['kaprog'], SuratTugasTerbit::class);
});

it('nomor surat tugas sequential per periode (BR18)', function () {
    Storage::fake('local');
    $data = siapkanPenugasan();

    asHubin()->post('/hubin/surat-tugas', ['periode_id' => $data['periode']->id, 'lingkup' => 'global', 'berlaku_hingga' => today()->addMonth()->toDateString()])->assertRedirect();
    asHubin()->post('/hubin/surat-tugas', ['periode_id' => $data['periode']->id, 'lingkup' => 'global', 'berlaku_hingga' => today()->addMonth()->toDateString()])->assertRedirect();

    $surats = SuratTugasMonitoring::orderBy('id')->get();
    expect(str_starts_with($surats[0]->nomor_surat, '1/'))->toBeTrue();
    expect(str_starts_with($surats[1]->nomor_surat, '2/'))->toBeTrue();
});

it('surat tugas scoped wajib kompetensi keahlian', function () {
    $data = siapkanPenugasan();

    asHubin()->post('/hubin/surat-tugas', [
        'periode_id' => $data['periode']->id,
        'lingkup' => 'scoped',
        'berlaku_hingga' => today()->addMonth()->toDateString(),
    ])->assertSessionHasErrors('kompetensi_keahlian_id');
});

it('hanya hubin yang bisa terbitkan surat tugas', function () {
    $data = siapkanPenugasan();

    asSiswa()->post('/hubin/surat-tugas', [
        'periode_id' => $data['periode']->id,
        'lingkup' => 'global',
        'berlaku_hingga' => today()->addMonth()->toDateString(),
    ])->assertForbidden();

    expect(SuratTugasMonitoring::count())->toBe(0);
});

// ─── Laporan Kunjungan — gated Surat Tugas (BR22) ─────────────

it('kunjungan ditolak 403 tanpa surat tugas aktif (BR22)', function () {
    Storage::fake('local');
    $data = siapkanPenugasan();
    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['pembimbing']->id,
    ]);
    $penugasan = PenugasanPembimbing::first();

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/kunjungan/{$penugasan->id}", [
            'tanggal_kunjungan' => today()->toDateString(),
        ])
        ->assertForbidden();

    expect(KunjunganMonitoring::count())->toBe(0);
});

it('kunjungan berhasil dengan surat tugas global aktif + notif ke siswa & hubin', function () {
    Storage::fake('local');
    Notification::fake();
    $data = siapkanPenugasan();
    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['pembimbing']->id,
    ]);
    asHubin()->post('/hubin/surat-tugas', ['periode_id' => $data['periode']->id, 'lingkup' => 'global', 'berlaku_hingga' => today()->addMonth()->toDateString()]);
    $penugasan = PenugasanPembimbing::first();

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/kunjungan/{$penugasan->id}", [
            'tanggal_kunjungan' => today()->toDateString(),
            'catatan' => 'Monitoring rutin.',
        ])
        ->assertRedirect();

    $kunjungan = KunjunganMonitoring::first();
    expect($kunjungan)->not->toBeNull();
    expect($kunjungan->penugasan_id)->toBe($penugasan->id);
    expect($kunjungan->catatan)->toBe('Monitoring rutin.');

    Notification::assertSentTo($data['siswa'], KunjunganBaru::class);
    Notification::assertSentTo(User::role('hubin')->first(), KunjunganBaru::class);
});

it('kunjungan ditolak saat berlaku_hingga surat tugas lewat', function () {
    Storage::fake('local');
    $data = siapkanPenugasan();
    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['pembimbing']->id,
    ]);
    SuratTugasMonitoring::create([
        'periode_id' => $data['periode']->id,
        'lingkup' => 'global',
        'diterbitkan_by' => hubinUser()->id,
        'nomor_surat' => 'ST-001',
        'tanggal_terbit' => today()->subDays(5)->toDateString(),
        'status' => 'aktif',
        'berlaku_hingga' => today()->subDay()->toDateString(),
    ]);
    $penugasan = PenugasanPembimbing::first();

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/kunjungan/{$penugasan->id}", [
            'tanggal_kunjungan' => today()->toDateString(),
        ])
        ->assertForbidden();

    expect(KunjunganMonitoring::count())->toBe(0);
});

it('kunjungan ditolak untuk jurusan lain pada surat tugas scoped', function () {
    Storage::fake('local');
    $data = siapkanPenugasan();
    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['pembimbing']->id,
    ]);
    $kkLain = KompetensiKeahlian::factory()->create();
    asHubin()->post('/hubin/surat-tugas', [
        'periode_id' => $data['periode']->id,
        'lingkup' => 'scoped',
        'kompetensi_keahlian_id' => $kkLain->id,
        'berlaku_hingga' => today()->addMonth()->toDateString(),
    ]);
    $penugasan = PenugasanPembimbing::first();

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/kunjungan/{$penugasan->id}", [
            'tanggal_kunjungan' => today()->toDateString(),
        ])
        ->assertForbidden();
});

it('kunjungan berhasil pada surat tugas scoped jurusan yang cocok', function () {
    Storage::fake('local');
    $data = siapkanPenugasan();
    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['pembimbing']->id,
    ]);
    asHubin()->post('/hubin/surat-tugas', [
        'periode_id' => $data['periode']->id,
        'lingkup' => 'scoped',
        'kompetensi_keahlian_id' => $data['kk']->id,
        'berlaku_hingga' => today()->addMonth()->toDateString(),
    ]);
    $penugasan = PenugasanPembimbing::first();

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/kunjungan/{$penugasan->id}", [
            'tanggal_kunjungan' => today()->toDateString(),
        ])
        ->assertRedirect();

    expect(KunjunganMonitoring::count())->toBe(1);
});

it('kunjungan mengarsip dokumentasi multipel', function () {
    Storage::fake('local');
    Notification::fake();
    $data = siapkanPenugasan();
    $this->actingAs($data['kaprog'])->post("/kaprog/penugasan/{$data['pengajuan']->id}", [
        'guru_pembimbing_id' => $data['pembimbing']->id,
    ]);
    asHubin()->post('/hubin/surat-tugas', ['periode_id' => $data['periode']->id, 'lingkup' => 'global', 'berlaku_hingga' => today()->addMonth()->toDateString()]);
    $penugasan = PenugasanPembimbing::first();

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/kunjungan/{$penugasan->id}", [
            'tanggal_kunjungan' => today()->toDateString(),
            'dokumentasi' => [
                UploadedFile::fake()->image('foto1.jpg'),
                UploadedFile::fake()->image('foto2.jpg'),
            ],
        ])
        ->assertRedirect();

    $paths = json_decode(KunjunganMonitoring::first()->dokumentasi_path, true);
    expect($paths)->toHaveCount(2);
    foreach ($paths as $path) {
        expect(Storage::exists($path))->toBeTrue();
    }
});

// ─── Request Surat Tugas dari Kaprog ─────────────────────────

it('kaprog meminta surat tugas scoped ke hubin', function () {
    Notification::fake();
    $data = siapkanPenugasan();
    $hubin = hubinUser();

    $this->actingAs($data['kaprog'])
        ->post('/kaprog/surat-tugas/request', [
            'periode_id' => $data['periode']->id,
            'kompetensi_keahlian_id' => $data['kk']->id,
            'catatan' => 'Mohon terbitkan surat tugas.',
        ])
        ->assertRedirect();

    Notification::assertSentTo($hubin, RequestSuratTugas::class);
});

// ─── Halaman M5 ───────────────────────────────────────────────

it('halaman penugasan kaprog dan kunjungan pembimbing bisa diakses', function () {
    $data = siapkanPenugasan();

    $this->actingAs($data['kaprog'])->get('/kaprog/penugasan')->assertOk();
    $this->actingAs($data['pembimbing'])->get('/guru-pembimbing/kunjungan')->assertOk();
    asHubin()->get('/hubin/surat-tugas')->assertOk();
});
