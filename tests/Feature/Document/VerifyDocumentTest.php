<?php

use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\DocumentQrToken;
use App\Models\PengajuanPkl;
use App\Services\DocumentService;
use App\Support\JakartaTime;
use Carbon\CarbonInterface;

/**
 * @return array{group: PengajuanPkl, company: Company, token: string, terbit: CarbonInterface}
 */
function pklVerifyContext(): array
{
    $period = AcademicPeriod::factory()->create();
    $company = Company::factory()->create();

    $group = PengajuanPkl::query()->create([
        'company_id' => $company->id,
        'academic_period_id' => $period->id,
        'tahun_ajaran' => '2025/2026',
        'nomor_urut' => 1,
        'nomor_surat' => '421.5/0001/SMKN.1-CADISDIK WIL 3',
    ]);

    $service = app(DocumentService::class);
    $token = $service->verificationToken(DocumentService::JENIS_PENGANTAR, (int) $group->getKey());
    $terbit = now()->subDay();

    DocumentQrToken::query()->create([
        'token_hash' => hash('sha256', $token),
        'document_type' => DocumentService::JENIS_PENGANTAR,
        'document_id' => $group->getKey(),
        'nomor_surat' => $group->nomor_surat,
        'status' => 'valid',
        'issued_at' => $terbit,
    ]);

    return compact('group', 'company', 'token', 'terbit');
}

test('halaman verifikasi hanya menampilkan nomor, status, dan tanggal terbit', function () {
    $context = pklVerifyContext();

    $this->get(route('verify', ['token' => $context['token']]))
        ->assertOk()
        ->assertSee($context['group']->nomor_surat)
        ->assertSee('Terbit')
        ->assertSee(JakartaTime::humanDate($context['terbit']))
        // Data lain tidak boleh bocor (KP-F12-3).
        ->assertDontSee($context['company']->nama)
        ->assertDontSee($context['company']->alamat);
});

test('token dicabut menghasilkan pesan netral yang sama dengan token tidak dikenal', function () {
    $context = pklVerifyContext();
    $pesanNetral = __('pkl.document.verify.tidak_ditemukan');

    DocumentQrToken::query()->update([
        'status' => 'dicabut',
        'revoked_at' => now(),
    ]);

    $this->get(route('verify', ['token' => $context['token']]))
        ->assertOk()
        ->assertSee($pesanNetral)
        ->assertDontSee($context['group']->nomor_surat);

    $this->get(route('verify', ['token' => str_repeat('a', 64)]))
        ->assertOk()
        ->assertSee($pesanNetral)
        ->assertDontSee($context['group']->nomor_surat);
});
