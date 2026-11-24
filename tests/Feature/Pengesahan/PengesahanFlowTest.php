<?php

use App\Actions\Pengesahan\BulkApproveSignatures;
use App\Actions\Pengesahan\IssueSignerLink;
use App\Actions\Pengesahan\RejectStep;
use App\Actions\Pengesahan\SignStep;
use App\Actions\Pengesahan\UploadRevisiLaporan;
use App\Enums\PengajuanStatus;
use App\Enums\Role;
use App\Exceptions\DocumentIssuanceHeld;
use App\Filament\Kaprog\Pages\PengesahanLaporan as PengesahanLaporanPage;
use App\Livewire\Portal\PengesahanKepsek;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\DocumentSignature;
use App\Models\ExternalSession;
use App\Models\GuruPembimbingPlotting;
use App\Models\Kelas;
use App\Models\LaporanVersion;
use App\Models\MagicLink;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\SignerLinkNotification;
use App\Services\ExternalOtpService;
use App\Services\MagicLinkService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

const PENGESAHAN_PDF = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF";

/**
 * @param  array<string, mixed>  $companyAttributes
 * @return array<string, mixed>
 */
function pengesahanContext(array $companyAttributes = []): array
{
    Storage::fake('local');
    Notification::fake();
    Storage::disk('local')->put('laporan/v1.pdf', PENGESAHAN_PDF);

    $kaprog = pklUser(['kaprog']);
    $jurusanId = (int) $kaprog->roleScopeId(Role::Kaprog);
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusanId]);
    $siswa = User::factory()->siswa($jurusanId, (int) $kelas->getKey())->create();
    UserRole::query()->create(['user_id' => $siswa->getKey(), 'role' => 'siswa']);
    $guru = pklUser(['guru_pembimbing']);
    $kepsek = pklUser(['kepsek']);
    $hubin = pklUser(['hubin']);
    $period = AcademicPeriod::factory()->create();

    $company = Company::factory()->create(array_merge([
        'pembimbing_industri_nama' => 'Budi Industri',
        'pembimbing_industri_email' => 'budi@industri.test',
        'pembimbing_industri_email_verified_at' => now(),
        'kepala_industri_nama' => 'Sari Kepala',
        'kepala_industri_email' => 'sari@industri.test',
        'kepala_industri_email_verified_at' => now(),
    ], $companyAttributes));

    $group = PengajuanPkl::query()->create([
        'company_id' => $company->getKey(),
        'academic_period_id' => $period->getKey(),
        'status' => 'berjalan',
    ]);

    $pengajuan = PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $group->getKey(),
        'company_id' => $company->getKey(),
        'academic_period_id' => $period->getKey(),
        'siswa_id' => $siswa->getKey(),
        'status' => PengajuanStatus::SedangPkl->value,
        'is_provisional' => false,
        'snapshot_company_json' => ['nama' => $company->nama],
        'tanggal_mulai_pkl' => '2026-01-05',
        'tanggal_selesai_pkl' => '2026-04-30',
    ]);

    GuruPembimbingPlotting::query()->create([
        'company_id' => $company->getKey(),
        'academic_period_id' => $period->getKey(),
        'guru_id' => $guru->getKey(),
        'plotted_by' => $kaprog->getKey(),
        'plotted_at' => now(),
    ]);

    $version = LaporanVersion::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'version_number' => 1,
        'file_path' => 'laporan/v1.pdf',
        'created_by' => $guru->getKey(),
    ]);

    DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'laporan_version_id' => $version->getKey(),
        'step_order' => 1,
        'signer_type' => 'guru_pembimbing',
        'signer_user_id' => $guru->getKey(),
        'status' => 'signed',
        'signed_at' => now(),
    ]);

    $step2 = DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'laporan_version_id' => $version->getKey(),
        'step_order' => 2,
        'signer_type' => 'kaprog',
        'status' => 'pending',
    ]);

    return compact('kaprog', 'guru', 'kepsek', 'hubin', 'period', 'company', 'group', 'pengajuan', 'siswa', 'version', 'step2');
}

function pengesahanExternalSession(DocumentSignature $signature, string $type): ExternalSession
{
    $link = MagicLink::query()->create([
        'token_hash' => hash('sha256', Str::random(40)),
        'type' => $type,
        'reference_id' => $signature->getKey(),
        'expires_at' => now()->addDay(),
        'is_active' => true,
    ]);

    return ExternalSession::query()->create([
        'magic_link_id' => $link->getKey(),
        'otp_code_hash' => hash('sha256', '000000'),
        'otp_expires_at' => now()->addMinutes(5),
        'otp_attempt_count' => 0,
        'verified_at' => now(),
        'session_expires_at' => now()->addHours(24),
    ]);
}

function pengesahanSignExternal(DocumentSignature $signature, string $type): DocumentSignature
{
    return app(SignStep::class)->handle($signature, null, pengesahanExternalSession($signature, $type));
}

function pengesahanSignature(DocumentSignature $step2, int $order): DocumentSignature
{
    return DocumentSignature::query()
        ->where('laporan_version_id', $step2->laporan_version_id)
        ->where('step_order', $order)
        ->firstOrFail();
}

/** Majukan satu rantai sampai step 5 terbuka (step 2 internal, step 3 & 4 eksternal). */
function pengesahanAdvanceToStep5(array $context): DocumentSignature
{
    session(['active_role' => Role::Kaprog->value]);
    app(SignStep::class)->handle($context['step2'], $context['kaprog']);

    pengesahanSignExternal(pengesahanSignature($context['step2'], 3), MagicLinkService::TYPE_SIGNER_STEP3);
    pengesahanSignExternal(pengesahanSignature($context['step2'], 4), MagicLinkService::TYPE_SIGNER_STEP4);

    return pengesahanSignature($context['step2'], 5);
}

test('step 3 ditolak sebelum step 2 signed walau request langsung, dan sign ganda idempoten', function () {
    $context = pengesahanContext();

    $step3 = DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $context['pengajuan']->getKey(),
        'laporan_version_id' => $context['version']->getKey(),
        'step_order' => 3,
        'signer_type' => 'pembimbing_industri',
        'status' => 'pending',
    ]);

    $session = pengesahanExternalSession($step3, MagicLinkService::TYPE_SIGNER_STEP3);

    expect(fn () => app(SignStep::class)->handle($step3, null, $session))
        ->toThrow(ValidationException::class);

    expect($step3->refresh()->status)->toBe('pending');

    session(['active_role' => Role::Kaprog->value]);
    app(SignStep::class)->handle($context['step2'], $context['kaprog']);
    $signedAt = $context['step2']->refresh()->signed_at;

    app(SignStep::class)->handle($context['step2']->refresh(), $context['kaprog']);

    expect($context['step2']->refresh()->signed_at->equalTo($signedAt))->toBeTrue()
        ->and(pengesahanSignature($context['step2'], 3)->status)->toBe('pending');
});

test('tolak step 4 lalu revisi kembali ke step 4 tanpa mengubah step 1 sampai 3', function () {
    $context = pengesahanContext();
    session(['active_role' => Role::Kaprog->value]);
    app(SignStep::class)->handle($context['step2'], $context['kaprog']);

    $step3 = pengesahanSignature($context['step2'], 3);
    pengesahanSignExternal($step3, MagicLinkService::TYPE_SIGNER_STEP3);

    $step4 = pengesahanSignature($context['step2'], 4);
    $session4 = pengesahanExternalSession($step4, MagicLinkService::TYPE_SIGNER_STEP4);

    app(RejectStep::class)->handle($step4, 'Bab 4 perlu diperbaiki.', null, $session4);

    expect($step4->refresh()->status)->toBe('rejected')
        ->and($step4->signer_user_id)->toBeNull();

    $oldStatuses = DocumentSignature::query()
        ->where('laporan_version_id', $context['version']->getKey())
        ->whereIn('step_order', [1, 2, 3])
        ->orderBy('step_order')
        ->pluck('status', 'step_order')
        ->all();

    app(UploadRevisiLaporan::class)->handle(
        $context['siswa'],
        $context['pengajuan'],
        UploadedFile::fake()->createWithContent('revisi.pdf', PENGESAHAN_PDF),
    );

    $newVersion = LaporanVersion::query()->orderByDesc('version_number')->firstOrFail();

    expect($newVersion->version_number)->toBe(2)
        ->and($newVersion->signatures()->count())->toBe(1)
        ->and($newVersion->signatures()->first()->step_order)->toBe(4)
        ->and($newVersion->signatures()->first()->status)->toBe('pending')
        ->and(DocumentSignature::query()
            ->where('laporan_version_id', $context['version']->getKey())
            ->whereIn('step_order', [1, 2, 3])
            ->orderBy('step_order')
            ->pluck('status', 'step_order')
            ->all())->toBe($oldStatuses);
});

test('tolak step 2 internal mencatat aktor dan alasan', function () {
    $context = pengesahanContext();
    session(['active_role' => Role::Kaprog->value]);

    app(RejectStep::class)->handle($context['step2'], 'Data siswa belum lengkap.', $context['kaprog']);

    expect($context['step2']->refresh()->status)->toBe('rejected')
        ->and($context['step2']->signer_user_id)->toBe($context['kaprog']->getKey())
        ->and($context['step2']->rejection_note)->toBe('Data siswa belum lengkap.');
});

test('bulk approve sepuluh laporan memproses sembilan dan melaporkan satu kegagalan', function () {
    $context = pengesahanContext();

    $kelas = Kelas::factory()->create(['jurusan_id' => (int) $context['kaprog']->roleScopeId(Role::Kaprog)]);
    $ids = [$context['step2']->getKey()];

    for ($i = 0; $i < 8; $i++) {
        $student = User::factory()->siswa((int) $kelas->jurusan_id, (int) $kelas->getKey())->create();
        UserRole::query()->create(['user_id' => $student->getKey(), 'role' => 'siswa']);

        $pengajuan = PengajuanSiswa::query()->create([
            'pengajuan_pkl_id' => $context['group']->getKey(),
            'company_id' => $context['company']->getKey(),
            'academic_period_id' => $context['period']->getKey(),
            'siswa_id' => $student->getKey(),
            'status' => PengajuanStatus::SedangPkl->value,
            'is_provisional' => false,
            'snapshot_company_json' => ['nama' => $context['company']->nama],
            'tanggal_mulai_pkl' => '2026-01-05',
            'tanggal_selesai_pkl' => '2026-04-30',
        ]);

        $version = LaporanVersion::query()->create([
            'pengajuan_siswa_id' => $pengajuan->getKey(),
            'version_number' => 1,
            'file_path' => 'laporan/v1.pdf',
            'created_by' => $context['guru']->getKey(),
        ]);

        DocumentSignature::query()->create([
            'pengajuan_siswa_id' => $pengajuan->getKey(),
            'laporan_version_id' => $version->getKey(),
            'step_order' => 1,
            'signer_type' => 'guru_pembimbing',
            'signer_user_id' => $context['guru']->getKey(),
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        $ids[] = DocumentSignature::query()->create([
            'pengajuan_siswa_id' => $pengajuan->getKey(),
            'laporan_version_id' => $version->getKey(),
            'step_order' => 2,
            'signer_type' => 'kaprog',
            'status' => 'pending',
        ])->getKey();
    }

    session(['active_role' => Role::Kaprog->value]);
    $result = app(BulkApproveSignatures::class)->handle($context['kaprog'], [...$ids, 999999]);

    expect($result['approved'])->toHaveCount(9)
        ->and($result['failed'])->toHaveCount(1)
        ->and($result['failed'][0]['id'])->toBe(999999)
        ->and(DocumentSignature::query()->whereIn('id', $ids)->where('status', 'signed')->count())->toBe(9);
});

test('tautan step 3 dan 4 hanya terbit setelah step sebelumnya signed', function () {
    $context = pengesahanContext();

    $step3 = DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $context['pengajuan']->getKey(),
        'laporan_version_id' => $context['version']->getKey(),
        'step_order' => 3,
        'signer_type' => 'pembimbing_industri',
        'status' => 'pending',
    ]);

    session(['active_role' => Role::Kaprog->value]);

    expect(fn () => app(IssueSignerLink::class)->handle($context['kaprog'], $step3))
        ->toThrow(ValidationException::class);

    app(SignStep::class)->handle($context['step2'], $context['kaprog']);

    $issued = app(IssueSignerLink::class)->handle($context['kaprog'], $step3->refresh());

    expect($issued['link']->is_active)->toBeTrue()
        ->and($issued['link']->type)->toBe(MagicLinkService::TYPE_SIGNER_STEP3);

    Notification::assertSentOnDemand(SignerLinkNotification::class);

    $step4 = DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $context['pengajuan']->getKey(),
        'laporan_version_id' => $context['version']->getKey(),
        'step_order' => 4,
        'signer_type' => 'kepala_industri',
        'status' => 'pending',
    ]);

    expect(fn () => app(IssueSignerLink::class)->handle($context['kaprog'], $step4))
        ->toThrow(ValidationException::class);

    expect(MagicLink::query()->where('reference_id', $step4->getKey())->exists())->toBeFalse();
});

test('signer industri tanpa email terverifikasi menahan langkah dan memberi tahu hubin', function () {
    $context = pengesahanContext([
        'pembimbing_industri_email_verified_at' => null,
        'kepala_industri_email_verified_at' => null,
    ]);

    session(['active_role' => Role::Kaprog->value]);
    app(SignStep::class)->handle($context['step2'], $context['kaprog']);

    Notification::assertSentTo($context['hubin'], App\Notifications\Notification::class);

    $step3 = pengesahanSignature($context['step2'], 3);

    expect(fn () => app(IssueSignerLink::class)->handle($context['kaprog'], $step3))
        ->toThrow(DocumentIssuanceHeld::class)
        ->and(MagicLink::query()->count())->toBe(0);
});

test('setelah step 5 signed laporan menjadi legit dan masuk pool siap sidang', function () {
    $context = pengesahanContext();
    session(['active_role' => Role::Kaprog->value]);
    app(SignStep::class)->handle($context['step2'], $context['kaprog']);

    $step3 = pengesahanSignature($context['step2'], 3);
    $link3 = app(IssueSignerLink::class)->handle($context['kaprog'], $step3);

    Notification::fake();
    $code3 = app(ExternalOtpService::class)->issue($link3['link'], 'budi@industri.test');
    $session3 = ExternalSession::query()->where('magic_link_id', $link3['link']->getKey())->latest('id')->firstOrFail();
    app(ExternalOtpService::class)->verify($session3, $code3);
    app(SignStep::class)->handle($step3, null, $session3->refresh());

    $step4 = pengesahanSignature($context['step2'], 4);
    $link4 = app(IssueSignerLink::class)->handle($context['kaprog'], $step4);

    $code4 = app(ExternalOtpService::class)->issue($link4['link'], 'sari@industri.test');
    $session4 = ExternalSession::query()->where('magic_link_id', $link4['link']->getKey())->latest('id')->firstOrFail();
    app(ExternalOtpService::class)->verify($session4, $code4);
    app(SignStep::class)->handle($step4, null, $session4->refresh());

    $step5 = pengesahanSignature($context['step2'], 5);
    session(['active_role' => Role::Kepsek->value]);
    app(SignStep::class)->handle($step5, $context['kepsek']);

    expect($context['version']->refresh()->is_legit)->toBeTrue()
        ->and(DocumentSignature::query()
            ->where('pengajuan_siswa_id', $context['pengajuan']->getKey())
            ->where('status', 'signed')
            ->count())->toBe(5)
        ->and(LaporanVersion::query()->where('is_legit', true)->pluck('id')->all())
        ->toContain($context['version']->getKey());
});

test('tautan masuk yang tidak valid memakai pesan netral', function () {
    $this->get('/eksternal/masuk/token-palsu')
        ->assertOk()
        ->assertSee(__('pkl.eksternal.invalid_link'));
});

test('kaprog menandatangani step 2 dari panel pengesahan', function () {
    $context = pengesahanContext();

    Filament::setCurrentPanel(Filament::getPanel('kaprog'));
    $this->actingAs($context['kaprog'])->withSession(['active_role' => Role::Kaprog->value]);

    Livewire::test(PengesahanLaporanPage::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$context['step2']])
        ->callAction(TestAction::make('approve')->table($context['step2']))
        ->assertNotified(__('pkl.pengesahan.signed', ['step' => 2]));

    expect($context['step2']->refresh()->status)->toBe('signed');
});

test('bulk panel pengesahan melaporkan alasan tiap laporan yang gagal', function () {
    $context = pengesahanContext();

    // Step 3 ditandatangani signer industri lewat tautan, jadi bulk Kaprog harus melewatinya.
    $step3 = DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $context['pengajuan']->getKey(),
        'laporan_version_id' => $context['version']->getKey(),
        'step_order' => 3,
        'signer_type' => 'pembimbing_industri',
        'status' => 'pending',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('kaprog'));
    $this->actingAs($context['kaprog'])->withSession(['active_role' => Role::Kaprog->value]);

    Livewire::test(PengesahanLaporanPage::class)
        ->selectTableRecords([$context['step2'], $step3])
        ->callAction(TestAction::make('approveBulk')->table()->bulk())
        ->assertNotified(
            FilamentNotification::make()
                ->title('1 laporan disetujui, 1 gagal.')
                ->body('#'.$step3->getKey().': '.__('pkl.pengesahan.not_authorized'))
                ->status('warning')
                ->persistent(),
        );

    expect($context['step2']->refresh()->status)->toBe('signed')
        ->and($step3->refresh()->status)->toBe('pending');
});

test('portal kepsek hanya menampilkan step 5 dan menandatanganinya', function () {
    $context = pengesahanContext();
    $step5 = pengesahanAdvanceToStep5($context);

    $this->actingAs($context['kepsek'])->withSession(['active_role' => Role::Kepsek->value]);

    Livewire::test(PengesahanKepsek::class)
        ->assertSee($context['siswa']->name)
        ->assertSee('5. Kepsek')
        ->assertDontSee('2. Kaprog')
        ->call('approve', $step5->getKey())
        ->assertSet('flash', __('pkl.pengesahan.signed', ['step' => 5]));

    expect($step5->refresh()->status)->toBe('signed')
        ->and($context['version']->refresh()->is_legit)->toBeTrue();
});

test('portal kepsek menolak step 5 dan mencatat aktor', function () {
    $context = pengesahanContext();
    $step5 = pengesahanAdvanceToStep5($context);

    $this->actingAs($context['kepsek'])->withSession(['active_role' => Role::Kepsek->value]);

    $component = Livewire::test(PengesahanKepsek::class);

    $component->call('startReject', $step5->getKey())
        ->set('rejectNote', 'pendek')
        ->call('reject', $step5->getKey())
        ->assertHasErrors('rejectNote');

    expect($step5->refresh()->status)->toBe('pending');

    $component->set('rejectNote', 'Bab 5 perlu diperbaiki lebih dulu.')
        ->call('reject', $step5->getKey())
        ->assertHasNoErrors()
        ->assertSet('flash', __('pkl.pengesahan.rejected'));

    expect($step5->refresh()->status)->toBe('rejected')
        ->and($step5->signer_user_id)->toBe($context['kepsek']->getKey())
        ->and($step5->rejection_note)->toBe('Bab 5 perlu diperbaiki lebih dulu.');
});

test('bulk portal kepsek memproses tiap laporan independen dan melaporkan yang gagal', function () {
    $context = pengesahanContext();
    $step5 = pengesahanAdvanceToStep5($context);

    // Laporan kedua tanpa step 4 signed: bulk harus melewatinya, bukan membatalkan yang pertama.
    $kelas = Kelas::factory()->create(['jurusan_id' => (int) $context['kaprog']->roleScopeId(Role::Kaprog)]);
    $student = User::factory()->siswa((int) $kelas->jurusan_id, (int) $kelas->getKey())->create();
    UserRole::query()->create(['user_id' => $student->getKey(), 'role' => 'siswa']);

    $pengajuan = PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $context['group']->getKey(),
        'company_id' => $context['company']->getKey(),
        'academic_period_id' => $context['period']->getKey(),
        'siswa_id' => $student->getKey(),
        'status' => PengajuanStatus::SedangPkl->value,
        'is_provisional' => false,
        'snapshot_company_json' => ['nama' => $context['company']->nama],
        'tanggal_mulai_pkl' => '2026-01-05',
        'tanggal_selesai_pkl' => '2026-04-30',
    ]);

    $version = LaporanVersion::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'version_number' => 1,
        'file_path' => 'laporan/v1.pdf',
        'created_by' => $context['guru']->getKey(),
    ]);

    $orphanStep5 = DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'laporan_version_id' => $version->getKey(),
        'step_order' => 5,
        'signer_type' => 'kepsek',
        'status' => 'pending',
    ]);

    $this->actingAs($context['kepsek'])->withSession(['active_role' => Role::Kepsek->value]);

    Livewire::test(PengesahanKepsek::class)
        ->set('selected', [$step5->getKey(), $orphanStep5->getKey()])
        ->call('approveSelected')
        ->assertSet('bulkResult.approved', [$step5->getKey()])
        ->assertSet('flash', '1 laporan disetujui, 1 gagal.')
        ->assertSee('Laporan yang gagal:')
        ->assertSee($student->name);

    expect($step5->refresh()->status)->toBe('signed')
        ->and($orphanStep5->refresh()->status)->toBe('pending');
});
