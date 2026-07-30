<?php

use App\Models\PengajuanPkl;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\SuratBalasan;
use App\Models\SuratPengantar;
use App\Notifications\SuratBalasanDitolak;
use App\Notifications\SuratBalasanDiupload;
use App\Notifications\SuratPengantarTerbit;
use App\Support\PenomoranSurat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// ─── Surat Pengantar ─────────────────────────────────────────

it('hanya hubin yang bisa mengakses halaman bundling', function () {
    asSiswa()->get('/hubin/surat-pengantar')->assertForbidden();

    $p = Perusahaan::factory()->create();
    $pr = PeriodePkl::factory()->create();
    asSiswa()->get("/hubin/surat-pengantar/{$p->id}/{$pr->id}")->assertForbidden();

    asHubin()->get('/hubin/surat-pengantar')->assertOk();
});

it('menampilkan daftar perusahaan yang punya pengajuan disetujui', function () {
    $data = siapkanSuratPengantar();

    asHubin()
        ->get('/hubin/surat-pengantar')
        ->assertOk()
        ->assertSee($data['perusahaan']->nama);
});

it('tombol cetak mendownload pdf tanpa mengubah data', function () {
    $data = siapkanSuratPengantar();

    asHubin()
        ->get("/hubin/surat-pengantar/{$data['perusahaan']->id}/{$data['periode']->id}/cetak")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    // Cetak tidak membuat record surat pengantar
    expect(SuratPengantar::count())->toBe(0);
});

it('tombol kirim membuat record surat pengantar dan mengirim notifikasi', function () {
    Notification::fake();
    $data = siapkanSuratPengantar();

    asHubin()->post("/hubin/surat-pengantar/{$data['perusahaan']->id}/{$data['periode']->id}/kirim");

    // Record surat pengantar dibuat
    expect(SuratPengantar::count())->toBe(1);
    $surat = SuratPengantar::first();
    expect($surat->nomor_surat)->not->toBeEmpty();
    expect($surat->perusahaan_id)->toBe($data['perusahaan']->id);
    expect($surat->file_path)->not->toBeNull();

    // Notifikasi ke siswa
    Notification::assertSentTo($data['user'], SuratPengantarTerbit::class);
});

it('nomor surat sequential per periode', function () {
    $data = siapkanSuratPengantar();

    asHubin()->post("/hubin/surat-pengantar/{$data['perusahaan']->id}/{$data['periode']->id}/kirim")->assertRedirect();
    asHubin()->post("/hubin/surat-pengantar/{$data['perusahaan']->id}/{$data['periode']->id}/kirim")->assertRedirect();
    asHubin()->post("/hubin/surat-pengantar/{$data['perusahaan']->id}/{$data['periode']->id}/kirim")->assertRedirect();

    expect(SuratPengantar::count())->toBe(3);
    $surats = SuratPengantar::orderBy('id')->get();
    expect(str_starts_with($surats[0]->nomor_surat, '1/'))->toBeTrue();
    expect(str_starts_with($surats[1]->nomor_surat, '2/'))->toBeTrue();
    expect(str_starts_with($surats[2]->nomor_surat, '3/'))->toBeTrue();
});

// ─── Surat Balasan ────────────────────────────────────────────

it('upload surat balasan ditolak (403) jika surat pengantar belum ada', function () {
    $user = siswaUser();
    $periode = PeriodePkl::factory()->create(['status_aktif' => true]);
    $pengajuan = PengajuanPkl::factory()->create([
        'siswa_id' => $user->siswaProfile->id,
        'perusahaan_id' => Perusahaan::factory(),
        'periode_id' => $periode->id,
        'status' => 'disetujui',
    ]);

    $file = UploadedFile::fake()->create('balasan.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post("/siswa/pengajuan/{$pengajuan->id}/surat-balasan", ['file' => $file])
        ->assertForbidden();
});

it('upload surat balasan berhasil mengubah status ke resmi_pkl otomatis (BR7)', function () {
    Storage::fake('local');
    Notification::fake();
    $data = siapkanSuratPengantar();
    $hubin = hubinUser();
    $surat = SuratPengantar::create([
        'nomor_surat' => PenomoranSurat::suratPengantar($data['periode']->id),
        'perusahaan_id' => $data['perusahaan']->id,
        'periode_id' => $data['periode']->id,
        'tanggal_terbit' => now()->toDateString(),
        'generated_by' => $hubin->id,
    ]);
    $surat->pengajuans()->attach($data['pengajuan']->id);
    $data['pengajuan']->refresh();

    $file = UploadedFile::fake()->create('balasan.pdf', 100, 'application/pdf');

    $this->actingAs($data['user'])
        ->post(route('siswa.surat-balasan.store', $data['pengajuan']->id), ['file' => $file])
        ->assertRedirect();

    $data['pengajuan']->refresh();
    expect($data['pengajuan']->status)->toBe('resmi_pkl');

    expect(SuratBalasan::where('pengajuan_id', $data['pengajuan']->id)->exists())->toBeTrue();

    Notification::assertSentTo($data['user'], SuratBalasanDiupload::class);
});

it('upload file non-pdf ditolak validasi sebelum mengubah status', function () {
    Storage::fake('local');
    $data = siapkanSuratPengantar();
    $surat = SuratPengantar::create([
        'nomor_surat' => PenomoranSurat::suratPengantar($data['periode']->id),
        'perusahaan_id' => $data['perusahaan']->id,
        'periode_id' => $data['periode']->id,
        'tanggal_terbit' => now()->toDateString(),
        'generated_by' => hubinUser()->id,
    ]);
    $surat->pengajuans()->attach($data['pengajuan']->id);

    $file = UploadedFile::fake()->create('balasan.txt', 100, 'text/plain');

    $this->actingAs($data['user'])
        ->post("/siswa/pengajuan/{$data['pengajuan']->id}/surat-balasan", ['file' => $file])
        ->assertSessionHasErrors('file');

    // Status tidak berubah
    $data['pengajuan']->refresh();
    expect($data['pengajuan']->status)->toBe('disetujui');
});

it('hubin dapat menolak surat balasan pasca-fakta (BR7)', function () {
    Storage::fake('local');
    Notification::fake();
    $data = siapkanSuratPengantar();
    $surat = SuratPengantar::create([
        'nomor_surat' => PenomoranSurat::suratPengantar($data['periode']->id),
        'perusahaan_id' => $data['perusahaan']->id,
        'periode_id' => $data['periode']->id,
        'tanggal_terbit' => now()->toDateString(),
        'generated_by' => hubinUser()->id,
    ]);
    $surat->pengajuans()->attach($data['pengajuan']->id);

    // Upload balasan dulu
    $file = UploadedFile::fake()->create('balasan.pdf', 100, 'application/pdf');
    $this->actingAs($data['user'])
        ->post("/siswa/pengajuan/{$data['pengajuan']->id}/surat-balasan", ['file' => $file]);

    expect($data['pengajuan']->fresh()->status)->toBe('resmi_pkl');

    // Hubin tolak pasca-fakta
    asHubin()
        ->post("/hubin/surat-pengantar/{$data['pengajuan']->id}/tolak-balasan", [
            'catatan' => 'Surat balasan bukan dari perusahaan yang dituju.',
        ])
        ->assertRedirect();

    // Status kembali ke disetujui
    $data['pengajuan']->refresh();
    expect($data['pengajuan']->status)->toBe('disetujui');

    // Record balasan dihapus
    expect(SuratBalasan::where('pengajuan_id', $data['pengajuan']->id)->exists())->toBeFalse();

    // Notifikasi penolakan dikirim
    Notification::assertSentTo($data['user'], SuratBalasanDitolak::class);
});

it('penolakan balasan wajib catatan minimal 15 karakter', function () {
    Storage::fake('local');
    $data = siapkanSuratPengantar();
    $surat = SuratPengantar::create([
        'nomor_surat' => PenomoranSurat::suratPengantar($data['periode']->id),
        'perusahaan_id' => $data['perusahaan']->id,
        'periode_id' => $data['periode']->id,
        'tanggal_terbit' => now()->toDateString(),
        'generated_by' => hubinUser()->id,
    ]);
    $surat->pengajuans()->attach($data['pengajuan']->id);
    $data['pengajuan']->refresh();

    // Upload balasan dulu agar status resmi_pkl
    $file = UploadedFile::fake()->create('balasan.pdf', 100, 'application/pdf');
    $this->actingAs($data['user'])
        ->post(route('siswa.surat-balasan.store', $data['pengajuan']->id), ['file' => $file])
        ->assertRedirect();
    expect($data['pengajuan']->fresh()->status)->toBe('resmi_pkl');

    asHubin()
        ->post("/hubin/surat-pengantar/{$data['pengajuan']->id}/tolak-balasan", [
            'catatan' => 'Terlalu pendek',
        ])
        ->assertSessionHasErrors('catatan');
});
