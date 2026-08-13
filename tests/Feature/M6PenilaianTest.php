<?php

use App\Models\FormulirPenilaian;
use App\Models\GuruProfile;
use App\Models\IndikatorPenilaian;
use App\Models\KompetensiKeahlian;
use App\Models\NilaiPenilaian;
use App\Models\PengajuanPkl;
use App\Models\PengajuanPklHistory;
use App\Models\PenugasanPembimbing;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\SectionPenilaian;
use App\Models\SiswaProfile;
use App\Models\SubSectionPenilaian;
use App\Models\User;
use App\Notifications\FormulirPenilaianTerbuka;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Setup minimal M6: pembimbing + siswa satu jurusan + pengajuan resmi_pkl + penugasan aktif.
 */
function siapkanPenilaian(array $overrides = []): array
{
    $kk = KompetensiKeahlian::factory()->create();
    $periode = PeriodePkl::factory()->create(array_merge([
        'tanggal_selesai' => now()->addMonth()->toDateString(),
    ], $overrides['periode'] ?? []));

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

    $pengajuan = PengajuanPkl::factory()->create([
        'siswa_id' => $siswaProfile->id,
        'periode_id' => $periode->id,
        'perusahaan_id' => Perusahaan::factory()->create()->id,
        'status' => 'resmi_pkl',
    ]);

    $penugasan = PenugasanPembimbing::factory()->create([
        'guru_pembimbing_id' => $pembimbing->id,
        'pengajuan_id' => $pengajuan->id,
        'assigned_by' => $pembimbing->id,
        'status' => 'aktif',
    ]);

    return compact('kk', 'periode', 'pembimbing', 'siswa', 'pengajuan', 'penugasan');
}

/**
 * Indikator: 1 umum (tanpa section) + 1 umum ber-section + 1 teknis jurusan (tanpa section).
 */
function siapkanIndikator(string $kompetensiId): array
{
    $umum = IndikatorPenilaian::factory()->create([
        'kompetensi_keahlian_id' => null,
        'nomor_urut' => 1,
    ]);

    $umumBerSection = IndikatorPenilaian::factory()->create([
        'kompetensi_keahlian_id' => null,
        'nomor_urut' => 2,
    ]);
    $section1 = SectionPenilaian::factory()->create(['indikator_penilaian_id' => $umumBerSection->id, 'nomor_urut' => 1]);
    $section2 = SectionPenilaian::factory()->create(['indikator_penilaian_id' => $umumBerSection->id, 'nomor_urut' => 2]);

    $teknis = IndikatorPenilaian::factory()->create([
        'kompetensi_keahlian_id' => $kompetensiId,
        'nomor_urut' => 1,
    ]);

    return compact('umum', 'umumBerSection', 'section1', 'section2', 'teknis');
}

function uploadLaporan(PengajuanPkl $pengajuan): void
{
    $pengajuan->update([
        'laporan_akhir_path' => 'laporan-akhir/tes.pdf',
        'laporan_akhir_uploaded_at' => now(),
    ]);
}

// ─── Upload Laporan Akhir (BR12, BR32) ─────────────────────────────

it('siswa upload laporan akhir: file tersimpan + notif pembimbing + gate terbuka', function () {
    Storage::fake('local');
    Notification::fake();
    $data = siapkanPenilaian();

    $this->actingAs($data['siswa'])
        ->post("/siswa/pengajuan/{$data['pengajuan']->id}/laporan-akhir", [
            'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHas('sukses');

    $data['pengajuan']->refresh();
    expect($data['pengajuan']->laporan_akhir_uploaded_at)->not->toBeNull();
    expect($data['pengajuan']->laporan_akhir_path)->not->toBeNull();
    Storage::disk('local')->assertExists($data['pengajuan']->laporan_akhir_path);

    Notification::assertSentTo($data['pembimbing'], FormulirPenilaianTerbuka::class);
});

it('siswa ditolak upload laporan akhir sebelum status resmi_pkl', function () {
    $data = siapkanPenilaian();
    $data['pengajuan']->update(['status' => 'disetujui']);

    $this->actingAs($data['siswa'])
        ->post("/siswa/pengajuan/{$data['pengajuan']->id}/laporan-akhir", [
            'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
        ])
        ->assertForbidden();
});

it('download laporan akhir milik siswa', function () {
    Storage::fake('local');
    Storage::disk('local')->put('laporan-akhir/tes.pdf', 'konten');
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);

    $this->actingAs($data['siswa'])
        ->get("/siswa/pengajuan/{$data['pengajuan']->id}/laporan-akhir")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

// ─── Gate Formulir Penilaian (BR32) ────────────────────────────────

it('formulir penilaian 403 sebelum laporan akhir diupload (BR32)', function () {
    $data = siapkanPenilaian();

    $this->actingAs($data['pembimbing'])
        ->get("/guru-pembimbing/penilaian/{$data['penugasan']->id}")
        ->assertForbidden();
});

it('formulir penilaian tampil setelah laporan akhir diupload', function () {
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);
    siapkanIndikator((string) $data['kk']->id);

    $this->actingAs($data['pembimbing'])
        ->get("/guru-pembimbing/penilaian/{$data['penugasan']->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('guru-pembimbing/penilaian/form'));
});

it('pembimbing lain ditolak akses formulir (BR11)', function () {
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);
    $pembimbingLain = User::factory()->create();
    $pembimbingLain->assignRole('guru_pembimbing');

    $this->actingAs($pembimbingLain)
        ->get("/guru-pembimbing/penilaian/{$data['penugasan']->id}")
        ->assertForbidden();
});

// ─── Submit & Kalkulasi (BR26, BR28, BR31) ─────────────────────────

it('submit formulir: nilai tersimpan + predikat sesuai skala fixed (BR28)', function () {
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);
    $ind = siapkanIndikator((string) $data['kk']->id);

    $payload = collect(array_merge(
        [['indikator_penilaian_id' => $ind['umum']->id, 'section_penilaian_id' => null, 'nilai' => 69]],
        [['indikator_penilaian_id' => null, 'section_penilaian_id' => $ind['section1']->id, 'nilai' => 80]],
        [['indikator_penilaian_id' => null, 'section_penilaian_id' => $ind['section2']->id, 'nilai' => 91]],
        [['indikator_penilaian_id' => $ind['teknis']->id, 'section_penilaian_id' => null, 'nilai' => 90]],
    ))->values()->all();

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/penilaian/{$data['penugasan']->id}", [
            'sakit' => 1, 'izin' => 2, 'alpa' => 0,
            'catatan_guru_pembimbing' => 'Siswa rajin.',
            'nama_pembimbing_industri' => 'Bpk Andi',
            'nilai' => $payload,
        ])
        ->assertRedirect(route('guru-pembimbing.penilaian.index'));

    $formulir = FormulirPenilaian::first();
    expect($formulir)->not->toBeNull();
    expect($formulir->submitted_at)->not->toBeNull();
    expect($formulir->sakit)->toBe(1);
    expect($formulir->nama_pembimbing_industri)->toBe('Bpk Andi');
    expect($formulir->penugasan_id)->toBe($data['penugasan']->id);

    expect($formulir->nilaiPenilaians()->count())->toBe(4);
    expect(NilaiPenilaian::where('formulir_penilaian_id', $formulir->id)->where('nilai', 69)->first()->predikat)->toBe('K');
    expect(NilaiPenilaian::where('formulir_penilaian_id', $formulir->id)->where('nilai', 80)->first()->predikat)->toBe('C');
    expect(NilaiPenilaian::where('formulir_penilaian_id', $formulir->id)->where('nilai', 91)->first()->predikat)->toBe('SB');
    expect(NilaiPenilaian::where('formulir_penilaian_id', $formulir->id)->where('nilai', 90)->first()->predikat)->toBe('B');
});

it('nilai akhir dihitung 2 tahap: rata-rata leaf per kategori lalu rata-rata kategori (BR28)', function () {
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);
    $ind = siapkanIndikator((string) $data['kk']->id);

    // Umum: leaf 80 & 90 → rata 85. Teknis: leaf 100 → rata 100. Akhir: (85+100)/2 = 92.5
    $payload = [
        ['indikator_penilaian_id' => $ind['umum']->id, 'section_penilaian_id' => null, 'nilai' => 80],
        ['indikator_penilaian_id' => null, 'section_penilaian_id' => $ind['section1']->id, 'nilai' => 90],
        ['indikator_penilaian_id' => $ind['teknis']->id, 'section_penilaian_id' => null, 'nilai' => 100],
    ];

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/penilaian/{$data['penugasan']->id}", [
            'sakit' => 0, 'izin' => 0, 'alpa' => 0,
            'nilai' => $payload,
        ])
        ->assertRedirect();

    $formulir = FormulirPenilaian::first();
    expect($formulir->nilai_akhir)->toBe(92.5);
    expect((string) $formulir->nilai_akhir)->toBe('92.5');
});

it('nilai akhir mengenali leaf sub-section untuk klasifikasi kategori', function () {
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);
    $ind = siapkanIndikator((string) $data['kk']->id);

    // Indikator teknis ber-section yang leaf-nya diisi di level sub-section saja.
    $teknisSection = SectionPenilaian::factory()->create([
        'indikator_penilaian_id' => $ind['teknis']->id,
        'nomor_urut' => 1,
    ]);
    $sub = SubSectionPenilaian::factory()->create([
        'section_penilaian_id' => $teknisSection->id,
        'nomor_urut' => 1,
    ]);

    // Umum: 80. Teknis (via sub-section): 100 → (80+100)/2 = 90.
    $payload = [
        ['indikator_penilaian_id' => $ind['umum']->id, 'section_penilaian_id' => null, 'sub_section_penilaian_id' => null, 'nilai' => 80],
        ['indikator_penilaian_id' => null, 'section_penilaian_id' => null, 'sub_section_penilaian_id' => $sub->id, 'nilai' => 100],
    ];

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/penilaian/{$data['penugasan']->id}", [
            'sakit' => 0, 'izin' => 0, 'alpa' => 0,
            'nilai' => $payload,
        ])
        ->assertRedirect();

    $formulir = FormulirPenilaian::first();
    expect($formulir->nilai_akhir)->toBe(90.0);
});

it('submit sekali saja: submit kedua ditolak 403 (BR31)', function () {
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);
    $ind = siapkanIndikator((string) $data['kk']->id);

    $payload = [
        ['indikator_penilaian_id' => $ind['umum']->id, 'section_penilaian_id' => null, 'nilai' => 80],
        ['indikator_penilaian_id' => $ind['teknis']->id, 'section_penilaian_id' => null, 'nilai' => 90],
    ];

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/penilaian/{$data['penugasan']->id}", [
            'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'nilai' => $payload,
        ])
        ->assertRedirect();

    expect(FormulirPenilaian::count())->toBe(1);

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/penilaian/{$data['penugasan']->id}", [
            'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'nilai' => $payload,
        ])
        ->assertForbidden();

    expect(FormulirPenilaian::count())->toBe(1);
});

it('submit ditolak bila salah satu kategori tidak punya leaf terisi (BR31)', function () {
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);
    $ind = siapkanIndikator((string) $data['kk']->id);

    // Teknis dibiarkan kosong → validation error.
    $payload = [
        ['indikator_penilaian_id' => $ind['umum']->id, 'section_penilaian_id' => null, 'nilai' => 80],
        ['indikator_penilaian_id' => $ind['teknis']->id, 'section_penilaian_id' => null, 'nilai' => null],
    ];

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/penilaian/{$data['penugasan']->id}", [
            'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'nilai' => $payload,
        ])
        ->assertSessionHasErrors('nilai');

    expect(FormulirPenilaian::count())->toBe(0);
});

it('leaf section yang kosong tidak masuk hitungan nilai akhir (BR26)', function () {
    $data = siapkanPenilaian();
    uploadLaporan($data['pengajuan'], $data['siswa']);
    $ind = siapkanIndikator((string) $data['kk']->id);

    // Umum: hanya section1 diisi (90). Teknis: 100. Akhir: (90+100)/2 = 95
    $payload = [
        ['indikator_penilaian_id' => null, 'section_penilaian_id' => $ind['section1']->id, 'nilai' => 90],
        ['indikator_penilaian_id' => null, 'section_penilaian_id' => $ind['section2']->id, 'nilai' => null],
        ['indikator_penilaian_id' => $ind['teknis']->id, 'section_penilaian_id' => null, 'nilai' => 100],
    ];

    $this->actingAs($data['pembimbing'])
        ->post("/guru-pembimbing/penilaian/{$data['penugasan']->id}", [
            'sakit' => 0, 'izin' => 0, 'alpa' => 0, 'nilai' => $payload,
        ])
        ->assertRedirect();

    $formulir = FormulirPenilaian::first();
    expect($formulir->nilai_akhir)->toBe(95.0);
});

// ─── Auto Selesai PKL (BR13) ───────────────────────────────────────

it('command pkl:tandai-selesai menandai selesai hanya jika 3 syarat terpenuhi', function () {
    $data = siapkanPenilaian([
        'periode' => ['tanggal_selesai' => now()->subDay()->toDateString()],
    ]);
    uploadLaporan($data['pengajuan'], $data['siswa']);
    $formulir = FormulirPenilaian::factory()->create([
        'penugasan_id' => $data['penugasan']->id,
        'siswa_id' => $data['siswa']->id,
        'periode_pkl_id' => $data['periode']->id,
        'guru_pembimbing_id' => $data['pembimbing']->id,
        'nilai_akhir' => 85,
        'submitted_at' => now(),
    ]);

    $this->artisan('pkl:tandai-selesai')->assertSuccessful();

    $data['pengajuan']->refresh();
    expect($data['pengajuan']->status)->toBe('selesai_pkl');
    expect(PengajuanPklHistory::where('pengajuan_id', $data['pengajuan']->id)
        ->where('status_ke', 'selesai_pkl')->exists())->toBeTrue();
});

it('command pkl:tandai-selesai tidak menyentuh pengajuan yang baru penuhi 1-2 syarat', function () {
    // Syarat (a) periode berakhir + (b) laporan ada, tapi (c) formulir belum disubmit.
    $data = siapkanPenilaian([
        'periode' => ['tanggal_selesai' => now()->subDay()->toDateString()],
    ]);
    uploadLaporan($data['pengajuan'], $data['siswa']);

    $this->artisan('pkl:tandai-selesai')->assertSuccessful();
    expect($data['pengajuan']->refresh()->status)->toBe('resmi_pkl');

    // Syarat (a)+(c) tapi (b) laporan belum ada.
    $data2 = siapkanPenilaian([
        'periode' => ['tanggal_selesai' => now()->subDay()->toDateString()],
    ]);
    FormulirPenilaian::factory()->create([
        'penugasan_id' => $data2['penugasan']->id,
        'siswa_id' => $data2['siswa']->id,
        'periode_pkl_id' => $data2['periode']->id,
        'guru_pembimbing_id' => $data2['pembimbing']->id,
        'submitted_at' => now(),
    ]);

    $this->artisan('pkl:tandai-selesai')->assertSuccessful();
    expect($data2['pengajuan']->refresh()->status)->toBe('resmi_pkl');
});
