<?php

use App\Actions\Bimbingan\ApproveLaporanFinal;
use App\Actions\Bimbingan\GiveFeedback;
use App\Actions\Bimbingan\MarkBagianAkhir;
use App\Actions\Bimbingan\SubmitBimbingan;
use App\Actions\Pengajuan\ApproveWithdrawal;
use App\Enums\PengajuanStatus;
use App\Filament\Guru\Pages\BimbinganSiswa;
use App\Models\AcademicPeriod;
use App\Models\BimbinganSubmission;
use App\Models\Company;
use App\Models\DocumentSignature;
use App\Models\GuruPembimbingPlotting;
use App\Models\Kelas;
use App\Models\LaporanVersion;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Models\UserRole;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/** @return array{pengajuan: PengajuanSiswa, siswa: User, guru: User, kaprog: User} */
function bimbinganContext(bool $provisional = false): array
{
    Storage::fake('local');
    Notification::fake();

    $kaprog = pklUser(['kaprog']);
    $jurusanId = $kaprog->roleScopeId('kaprog');
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusanId]);
    $siswa = User::factory()->siswa((int) $jurusanId, (int) $kelas->getKey())->create();
    UserRole::query()->create(['user_id' => $siswa->getKey(), 'role' => 'siswa']);
    $guru = pklUser(['guru_pembimbing']);
    $period = AcademicPeriod::factory()->create();
    $company = Company::factory()->create();
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
        'is_provisional' => $provisional,
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

    return compact('pengajuan', 'siswa', 'guru', 'kaprog');
}

function bimbinganFile(string $name = 'bimbingan.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF",
    );
}

function uploadBimbingan(array $context, ?string $keterangan = null): BimbinganSubmission
{
    session(['active_role' => 'siswa']);

    return app(SubmitBimbingan::class)->handle($context['siswa'], $context['pengajuan'], bimbinganFile(), $keterangan);
}

test('provisional student is rejected on direct upload endpoint', function () {
    $context = bimbinganContext(provisional: true);

    expect(fn () => uploadBimbingan($context))
        ->toThrow(ValidationException::class);

    expect(BimbinganSubmission::query()->count())->toBe(0);
});

test('bagian akhir requires four interactions and rejects a renamed exe', function () {
    $context = bimbinganContext();
    session(['active_role' => 'siswa']);

    expect(fn () => app(SubmitBimbingan::class)->handle(
        $context['siswa'],
        $context['pengajuan'],
        UploadedFile::fake()->createWithContent('laporan.pdf', "<?php echo 'x'; ?>"),
    ))->toThrow(ValidationException::class);

    $submissions = [];

    for ($i = 0; $i < 3; $i++) {
        $submissions[] = uploadBimbingan($context);
    }

    expect(fn () => app(MarkBagianAkhir::class)->handle($context['siswa'], $submissions[0]))
        ->toThrow(ValidationException::class);

    $fourth = uploadBimbingan($context);

    app(MarkBagianAkhir::class)->handle($context['siswa'], $fourth);

    expect($fourth->refresh()->is_bagian_akhir)->toBeTrue();
});

test('feedback transitions and non-supervisor guru is rejected', function () {
    $context = bimbinganContext();
    $submission = uploadBimbingan($context);
    $other = pklUser(['guru_pembimbing']);

    session(['active_role' => 'guru_pembimbing']);
    expect(fn () => app(GiveFeedback::class)->handle($submission, 'diterima', null, $other))
        ->toThrow(AuthorizationException::class);

    app(GiveFeedback::class)->handle($submission, 'revisi', 'Perbaiki bab 3.', $context['guru']);
    expect($submission->refresh()->status)->toBe('revisi');

    // Idempoten: keputusan kedua dengan nilai sama tidak mengubah efek.
    app(GiveFeedback::class)->handle($submission, 'revisi', null, $context['guru']);
    expect($submission->refresh()->status)->toBe('revisi');
});

test('approve laporan final creates version one with step 1 signed and step 2 pending', function () {
    $context = bimbinganContext();

    $submissions = [];
    for ($i = 0; $i < 4; $i++) {
        $submissions[] = uploadBimbingan($context);
    }

    $final = $submissions[3];
    app(MarkBagianAkhir::class)->handle($context['siswa'], $final);

    session(['active_role' => 'guru_pembimbing']);
    app(GiveFeedback::class)->handle($final, 'diterima', null, $context['guru']);

    $version = app(ApproveLaporanFinal::class)->handle($context['guru'], $context['pengajuan'], $final->refresh());

    $steps = DocumentSignature::query()->where('laporan_version_id', $version->getKey())
        ->orderBy('step_order')->get();

    expect($version->version_number)->toBe(1)
        ->and((bool) $version->is_legit)->toBeFalse()
        ->and($steps)->toHaveCount(2)
        ->and($steps[0]->step_order)->toBe(1)
        ->and($steps[0]->status)->toBe('signed')
        ->and($steps[0]->signer_user_id)->toBe($context['guru']->getKey())
        ->and($steps[1]->step_order)->toBe(2)
        ->and($steps[1]->status)->toBe('pending');

    // Idempoten: panggilan kedua tidak membuat versi baru.
    $again = app(ApproveLaporanFinal::class)->handle($context['guru'], $context['pengajuan'], $final);

    expect($again->getKey())->toBe($version->getKey())
        ->and(LaporanVersion::query()->count())->toBe(1);
});

test('withdrawal archives bimbingan history and resets the count', function () {
    $context = bimbinganContext();
    uploadBimbingan($context);
    uploadBimbingan($context);

    $context['pengajuan']->update(['withdraw_requested_at' => now(), 'withdraw_reason' => 'Pindah tempat.']);

    session(['active_role' => 'kaprog']);
    app(ApproveWithdrawal::class)->handle($context['pengajuan']->refresh(), $context['kaprog']);

    expect(BimbinganSubmission::query()->where('is_archived', true)->count())->toBe(2);
});

test('halaman bimbingan guru hanya menampilkan siswa bimbingannya', function () {
    $context = bimbinganContext();
    uploadBimbingan($context);

    $otherGuru = pklUser(['guru_pembimbing']);
    $otherCompany = Company::factory()->create();
    $otherSiswa = User::factory()->siswa((int) $context['siswa']->jurusan_id, (int) $context['siswa']->kelas_id)->create();
    UserRole::query()->create(['user_id' => $otherSiswa->getKey(), 'role' => 'siswa']);
    $otherGroup = PengajuanPkl::query()->create([
        'company_id' => $otherCompany->getKey(),
        'academic_period_id' => $context['pengajuan']->academic_period_id,
        'status' => 'berjalan',
    ]);
    $otherPengajuan = PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $otherGroup->getKey(),
        'company_id' => $otherCompany->getKey(),
        'academic_period_id' => $context['pengajuan']->academic_period_id,
        'siswa_id' => $otherSiswa->getKey(),
        'status' => PengajuanStatus::SedangPkl->value,
        'snapshot_company_json' => ['nama' => $otherCompany->nama],
        'tanggal_mulai_pkl' => '2026-01-05',
        'tanggal_selesai_pkl' => '2026-04-30',
    ]);
    GuruPembimbingPlotting::query()->create([
        'company_id' => $otherCompany->getKey(),
        'academic_period_id' => $context['pengajuan']->academic_period_id,
        'guru_id' => $otherGuru->getKey(),
        'plotted_by' => $context['kaprog']->getKey(),
        'plotted_at' => now(),
    ]);

    session(['active_role' => 'siswa']);
    app(SubmitBimbingan::class)->handle($otherSiswa, $otherPengajuan, bimbinganFile(), null);

    Filament::setCurrentPanel(Filament::getPanel('guru'));
    $this->actingAs($context['guru'])->withSession(['active_role' => 'guru_pembimbing']);

    Livewire::test(BimbinganSiswa::class)
        ->assertOk()
        ->assertSee($context['siswa']->name)
        ->assertDontSee($otherSiswa->name);
});

test('guru memberi feedback dan ACC laporan final dari tabel bimbingan', function () {
    $context = bimbinganContext();
    $submissions = [];

    for ($i = 0; $i < 4; $i++) {
        $submissions[] = uploadBimbingan($context);
    }

    $final = $submissions[3];
    app(MarkBagianAkhir::class)->handle($context['siswa'], $final);

    Filament::setCurrentPanel(Filament::getPanel('guru'));
    $this->actingAs($context['guru'])->withSession(['active_role' => 'guru_pembimbing']);

    Livewire::test(BimbinganSiswa::class)
        ->callAction(TestAction::make('accept')->table($final), ['note' => 'Bagian akhir sudah lengkap.'])
        ->assertNotified(__('pkl.bimbingan.feedback_saved'));

    expect($final->refresh()->status)->toBe('diterima');

    Livewire::test(BimbinganSiswa::class)
        ->callAction(TestAction::make('approveFinal')->table($final->refresh()))
        ->assertNotified(__('pkl.bimbingan.final_approved'));

    expect(LaporanVersion::query()->count())->toBe(1);
});
