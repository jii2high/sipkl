<?php

use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\GuruPembimbingPlotting;
use App\Models\SignatureAsset;
use App\Models\SuratTugasPembimbing;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{surat: SuratTugasPembimbing, guru: User, lain: User, siswa: User, hubin: User}
 */
function pklDownloadContext(): array
{
    Storage::fake('local');

    $hubin = pklUser(['hubin']);
    $kepsek = pklUser(['kepsek']);
    $guru = pklUser(['guru_pembimbing']);
    $lain = pklUser(['guru_pembimbing']);
    $siswa = pklUser(['siswa']);

    $signature = SignatureAsset::query()->create([
        'owner_user_id' => $kepsek->getKey(),
        'file_path' => 'signature/ttd.png',
        'is_active' => true,
        'uploaded_by' => $hubin->getKey(),
    ]);

    $plotting = GuruPembimbingPlotting::query()->create([
        'company_id' => Company::factory()->create()->id,
        'academic_period_id' => AcademicPeriod::factory()->create()->id,
        'guru_id' => $guru->getKey(),
        'plotted_by' => $hubin->getKey(),
    ]);

    Storage::disk('local')->put('documents/surat_tugas_pembimbing/uji.pdf', '%PDF-1.4');

    $surat = SuratTugasPembimbing::query()->create([
        'guru_pembimbing_plotting_id' => $plotting->getKey(),
        'tahun_ajaran' => '2025/2026',
        'nomor_urut' => 1,
        'nomor_surat' => 'KPG.11.01/0001/SMKN.1-CADISDIK WIL 3',
        'signature_asset_id' => $signature->getKey(),
        'signed_at' => now(),
        'file_path' => 'documents/surat_tugas_pembimbing/uji.pdf',
    ]);

    return compact('surat', 'guru', 'lain', 'siswa', 'hubin');
}

test('Hubin dan guru pemilik dapat mengunduh surat tugas', function () {
    $context = pklDownloadContext();
    $url = route('documents.download', ['jenis' => 'surat_tugas_pembimbing', 'id' => $context['surat']->getKey()]);

    $this->actingAs($context['hubin']);
    session(['active_role' => 'hubin']);
    $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');

    $this->actingAs($context['guru']);
    session(['active_role' => 'guru_pembimbing']);
    $this->get($url)->assertOk();
});

test('pengguna tak berwenang tidak dapat mengunduh surat', function () {
    $context = pklDownloadContext();
    $url = route('documents.download', ['jenis' => 'surat_tugas_pembimbing', 'id' => $context['surat']->getKey()]);

    $this->actingAs($context['lain']);
    session(['active_role' => 'guru_pembimbing']);
    $this->get($url)->assertForbidden();

    $this->actingAs($context['siswa']);
    session(['active_role' => 'siswa']);
    $this->get($url)->assertForbidden();
});

test('jenis surat tak dikenal dan berkas yang belum dibuat menghasilkan 404', function () {
    $context = pklDownloadContext();

    $this->actingAs($context['hubin']);
    session(['active_role' => 'hubin']);

    $this->get(route('documents.download', ['jenis' => 'entah', 'id' => 1]))->assertNotFound();

    Storage::disk('local')->delete('documents/surat_tugas_pembimbing/uji.pdf');

    $this->get(route('documents.download', [
        'jenis' => 'surat_tugas_pembimbing',
        'id' => $context['surat']->getKey(),
    ]))->assertNotFound();
});
