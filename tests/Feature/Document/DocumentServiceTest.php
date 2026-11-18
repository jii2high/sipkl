<?php

use App\Exceptions\DocumentIssuanceHeld;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\DocumentQrToken;
use App\Models\PengajuanPkl;
use App\Models\SignatureAsset;
use App\Models\User;
use App\Notifications\Notification as PklNotification;
use App\Services\DocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{hubin: User, kepsek: User, period: AcademicPeriod, company: Company, group: PengajuanPkl}
 */
function pklDocumentContext(bool $withSignature = true): array
{
    Storage::fake('local');
    Notification::fake();

    $hubin = pklUser(['hubin']);
    $kepsek = pklUser(['kepsek']);

    if ($withSignature) {
        $file = UploadedFile::fake()->image('ttd.png');
        $png = (string) file_get_contents($file->getRealPath());
        Storage::disk('local')->put('signature/ttd.png', $png);

        SignatureAsset::query()->create([
            'owner_user_id' => $kepsek->getKey(),
            'file_path' => 'signature/ttd.png',
            'is_active' => true,
            'uploaded_by' => $hubin->getKey(),
        ]);
    }

    $period = AcademicPeriod::factory()->create();
    $company = Company::factory()->create();

    $group = PengajuanPkl::query()->create([
        'company_id' => $company->id,
        'academic_period_id' => $period->id,
    ]);

    return compact('hubin', 'kepsek', 'period', 'company', 'group');
}

test('penerbitan ditahan dan Hubin diberi tahu bila TTD Kepsek belum ada', function () {
    $context = pklDocumentContext(withSignature: false);

    expect(fn () => app(DocumentService::class)->issue(
        $context['group'],
        DocumentService::JENIS_PENGANTAR,
        '2025/2026',
    ))->toThrow(DocumentIssuanceHeld::class);

    expect($context['group']->refresh()->nomor_surat)->toBeNull()
        ->and(DocumentQrToken::query()->count())->toBe(0);

    Notification::assertSentTo($context['hubin'], PklNotification::class);
});

test('penerbitan membuat nomor, token hash, PDF privat, dan idempoten', function () {
    $context = pklDocumentContext();
    $service = app(DocumentService::class);

    $service->issue($context['group'], DocumentService::JENIS_PENGANTAR, '2025/2026', $context['hubin']);

    $group = $context['group']->refresh();

    expect($group->nomor_urut)->toBe(1)
        ->and($group->nomor_surat)->toBe('421.5/0001/SMKN.1-CADISDIK WIL 3')
        ->and($group->signature_asset_id)->not->toBeNull()
        ->and($group->issued_by)->toBe($context['hubin']->getKey())
        ->and($group->issued_at)->not->toBeNull()
        ->and($group->file_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists((string) $group->file_path))->toBeTrue()
        ->and($group->status)->toBe('terbentuk');

    $token = DocumentQrToken::query()->sole();

    expect($token->nomor_surat)->toBe($group->nomor_surat)
        ->and($token->status)->toBe('valid')
        ->and($token->token_hash)->toBe(hash('sha256', $service->verificationToken(
            DocumentService::JENIS_PENGANTAR,
            (int) $group->getKey(),
        )))
        // Token mentah tidak pernah disimpan.
        ->and($token->token_hash)->not->toBe($service->verificationToken(
            DocumentService::JENIS_PENGANTAR,
            (int) $group->getKey(),
        ));

    // Panggilan kedua memakai nomor yang sama; counter tidak naik.
    $service->issue($group->refresh(), DocumentService::JENIS_PENGANTAR, '2025/2026');

    expect($group->refresh()->nomor_surat)->toBe('421.5/0001/SMKN.1-CADISDIK WIL 3')
        ->and(DocumentQrToken::query()->count())->toBe(1);
});

test('kegagalan pembuatan PDF lalu retry memakai nomor yang sama', function () {
    $context = pklDocumentContext();
    $service = app(DocumentService::class);

    $service->issue($context['group'], DocumentService::JENIS_PENGANTAR, '2025/2026');
    $nomor = (string) $context['group']->refresh()->nomor_surat;

    // Simulasi percobaan yang gagal: dokumen tidak ditemukan.
    expect(fn () => $service->renderPdf(DocumentService::JENIS_PENGANTAR, 999999))
        ->toThrow(RuntimeException::class);

    $path = $service->renderPdf(DocumentService::JENIS_PENGANTAR, (int) $context['group']->getKey());

    expect($context['group']->refresh()->nomor_surat)->toBe($nomor)
        ->and(Storage::disk('local')->exists($path))->toBeTrue()
        ->and($path)->toBe((string) $context['group']->file_path)
        ->and($path)->toStartWith('documents/surat_pengantar/');
});
