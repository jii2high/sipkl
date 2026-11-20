<?php

use App\Actions\Plotting\AssignGuru;
use App\Actions\Plotting\IssueSuratTugasPembimbing;
use App\Actions\Plotting\ReplaceGuru;
use App\Actions\Visitasi\ApproveVisitasi;
use App\Actions\Visitasi\IssueSuratTugasVisitasi;
use App\Actions\Visitasi\RejectVisitasi;
use App\Actions\Visitasi\RequestVisitasi;
use App\Actions\Visitasi\UploadDokumentasi;
use App\Enums\PengajuanStatus;
use App\Filament\App\Pages\PlottingGuru;
use App\Filament\App\Pages\Visitasi as VisitasiPage;
use App\Filament\Hubin\Pages\KelolaVisitasi;
use App\Jobs\ExpireVisitasiJob;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\DocumentQrToken;
use App\Models\GuruPembimbingPlotting;
use App\Models\Kelas;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\SignatureAsset;
use App\Models\SuratTugasPembimbing;
use App\Models\SuratTugasVisitasi;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VisitasiDokumentasi;
use App\Notifications\Notification as PklNotification;
use App\Services\PlottingService;
use App\Support\JakartaTime;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/** @return array{kaprog: User, guru: User, company: Company, period: AcademicPeriod, hubin: User} */
function plottingContext(bool $withSignature = true): array
{
    Storage::fake('local');
    Notification::fake();

    $kaprog = pklUser(['kaprog']);
    $jurusanId = $kaprog->roleScopeId('kaprog');
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusanId]);
    $student = User::factory()->siswa((int) $jurusanId, (int) $kelas->getKey())->create();
    UserRole::query()->create(['user_id' => $student->getKey(), 'role' => 'siswa']);
    $guru = pklUser(['guru_pembimbing']);
    $hubin = pklUser(['hubin']);
    $period = AcademicPeriod::factory()->create();
    $company = Company::factory()->create();
    $group = PengajuanPkl::query()->create([
        'company_id' => $company->getKey(),
        'academic_period_id' => $period->getKey(),
        'status' => 'berjalan',
    ]);

    PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $group->getKey(),
        'company_id' => $company->getKey(),
        'academic_period_id' => $period->getKey(),
        'siswa_id' => $student->getKey(),
        'status' => PengajuanStatus::SedangPkl->value,
        'snapshot_company_json' => ['nama' => $company->nama, 'alamat' => $company->alamat],
        'tanggal_mulai_pkl' => '2026-01-05',
        'tanggal_selesai_pkl' => '2026-04-30',
    ]);

    if ($withSignature) {
        Storage::disk('local')->put('signature/ttd.png', UploadedFile::fake()->image('ttd.png')->getContent());
        SignatureAsset::query()->create([
            'owner_user_id' => pklUser(['kepsek'])->getKey(),
            'file_path' => 'signature/ttd.png',
            'is_active' => true,
            'uploaded_by' => $hubin->getKey(),
        ]);
    }

    return compact('kaprog', 'guru', 'company', 'period', 'hubin');
}

function grantActiveRole(string $role): void
{
    session(['active_role' => $role]);
}

test('plotting issues one supervisor letter and repeat assignment stays idempotent', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');

    $plotting = app(AssignGuru::class)->handle(
        $context['company'],
        $context['period'],
        $context['guru'],
        $context['kaprog'],
    );

    $again = app(AssignGuru::class)->handle(
        $context['company'],
        $context['period'],
        $context['guru'],
        $context['kaprog'],
    );

    expect($again->getKey())->toBe($plotting->getKey())
        ->and(GuruPembimbingPlotting::query()->count())->toBe(1)
        ->and(SuratTugasPembimbing::query()->count())->toBe(1)
        ->and($plotting->suratTugas()->first()?->guru_id)->toBe($context['guru']->getKey())
        ->and(DocumentQrToken::query()->where('document_type', 'surat_tugas_pembimbing')->count())->toBe(1);
});

test('plotting remains saved and Hubin is notified when signature is missing', function () {
    $context = plottingContext(withSignature: false);
    grantActiveRole('kaprog');

    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);

    expect(GuruPembimbingPlotting::query()->count())->toBe(1)
        ->and(SuratTugasPembimbing::query()->count())->toBe(0);

    Notification::assertSentTo($context['hubin'], PklNotification::class);
});

test('replacing a plotted teacher revokes old letter and issues a new one', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');
    $plotting = app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $replacement = pklUser(['guru_pembimbing']);

    app(ReplaceGuru::class)->handle($plotting, $replacement, $context['kaprog']);

    $letters = SuratTugasPembimbing::query()->orderBy('id')->get();

    expect($letters)->toHaveCount(2)
        ->and($letters[0]->superseded_at)->not->toBeNull()
        ->and($letters[0]->guru_id)->toBe($context['guru']->getKey())
        ->and($letters[1]->guru_id)->toBe($replacement->getKey())
        ->and($plotting->refresh()->guru_id)->toBe($replacement->getKey())
        ->and(DocumentQrToken::query()->where('document_id', $letters[0]->getKey())->value('status'))->toBe('dicabut');
});

test('visitasi rejection requires a reason and rejected request can be resubmitted', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');
    $plotting = app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);
    grantActiveRole('hubin');

    expect(fn () => app(RejectVisitasi::class)->handle($visitasi, $context['hubin'], ''))
        ->toThrow(ValidationException::class);

    app(RejectVisitasi::class)->handle($visitasi, $context['hubin'], 'Jadwal perlu diperbaiki.');

    grantActiveRole('kaprog');
    $retry = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    expect($visitasi->refresh()->status)->toBe('ditolak')
        ->and($retry->getKey())->not->toBe($visitasi->getKey())
        ->and($retry->status)->toBe('diajukan')
        ->and($plotting->getKey())->not->toBeNull();
});

test('approved visitasi issues a letter and accepts private JPG documentation', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);
    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);

    $visitasi->refresh();
    grantActiveRole('guru_pembimbing');
    $documentation = app(UploadDokumentasi::class)->handle(
        $visitasi,
        $context['company']->getKey(),
        now()->timezone('Asia/Jakarta')->toDateString(),
        UploadedFile::fake()->image('kunjungan.jpg'),
        $context['guru'],
        'Kunjungan pembukaan PKL.',
    );

    expect($visitasi->nomor_surat)->not->toBeNull()
        ->and($visitasi->status)->toBe('disetujui')
        ->and($documentation)->toBeInstanceOf(VisitasiDokumentasi::class)
        ->and(Storage::disk('local')->exists($documentation->file_path))->toBeTrue();
});

test('visitasi expiration job changes status and audits only once when run twice', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);
    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);
    $visitasi->update(['tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => '2026-11-02']);
    Cache::flush();
    ExpireVisitasiJob::dispatchSync();

    Cache::flush();
    ExpireVisitasiJob::dispatchSync();

    expect($visitasi->refresh()->status)->toBe('kedaluwarsa')
        ->and(Activity::query()
            ->where('subject_type', SuratTugasVisitasi::class)
            ->where('subject_id', $visitasi->getKey())
            ->where('event', 'visitasi_kedaluwarsa')
            ->count())->toBe(1);
});

test('second assignment with different guru replaces plotting and audits the change', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');

    $first = app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $replacement = pklUser(['guru_pembimbing']);
    $second = app(AssignGuru::class)->handle($context['company'], $context['period'], $replacement, $context['kaprog']);

    expect($second->getKey())->toBe($first->getKey())
        ->and((int) $second->guru_id)->toBe((int) $replacement->getKey())
        ->and(SuratTugasPembimbing::query()->count())->toBe(2)
        ->and(Activity::query()
            ->where('subject_type', GuruPembimbingPlotting::class)
            ->where('subject_id', $first->getKey())
            ->where('event', 'guru_pembimbing_diganti')
            ->count())->toBe(1);
});

test('eligible companies eager loads plotting and student relations', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');

    $companies = app(PlottingService::class)
        ->eligibleCompanies($context['period'], $context['kaprog']);

    expect($companies->contains(fn (Company $company): bool => $company->getKey() === $context['company']->getKey()))->toBeTrue()
        ->and($companies->first()->jumlah_siswa)->toBe(1);
});

test('late student joins existing plotting without a second plotting', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');
    $plotting = app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);

    $jurusanId = (int) $context['kaprog']->roleScopeId('kaprog');
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusanId]);
    $student = User::factory()->siswa($jurusanId, (int) $kelas->getKey())->create();
    UserRole::query()->create(['user_id' => $student->getKey(), 'role' => 'siswa']);
    $group = PengajuanPkl::query()
        ->where('company_id', $context['company']->getKey())
        ->where('academic_period_id', $context['period']->getKey())
        ->firstOrFail();
    PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $group->getKey(),
        'company_id' => $context['company']->getKey(),
        'academic_period_id' => $context['period']->getKey(),
        'siswa_id' => $student->getKey(),
        'status' => PengajuanStatus::SedangPkl->value,
        'snapshot_company_json' => ['nama' => $context['company']->nama, 'alamat' => $context['company']->alamat],
        'tanggal_mulai_pkl' => '2026-02-01',
        'tanggal_selesai_pkl' => '2026-04-30',
    ]);

    $companies = app(PlottingService::class)->companiesForGuru($context['guru']);

    expect(GuruPembimbingPlotting::query()->count())->toBe(1)
        ->and(PengajuanSiswa::query()
            ->where('company_id', $context['company']->getKey())
            ->where('academic_period_id', $context['period']->getKey())
            ->where('status', PengajuanStatus::SedangPkl->value)
            ->count())->toBe(2)
        ->and($companies->contains(fn (Company $company): bool => $company->getKey() === $context['company']->getKey()))->toBeTrue()
        ->and((int) $plotting->refresh()->guru_id)->toBe((int) $context['guru']->getKey());
});

test('supervisor letter is reissued after kepsek signature becomes available', function () {
    $context = plottingContext(withSignature: false);
    grantActiveRole('kaprog');
    $plotting = app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);

    expect($plotting->suratTugas()->exists())->toBeFalse();

    Storage::disk('local')->put('signature/ttd-baru.png', UploadedFile::fake()->image('ttd-baru.png')->getContent());
    SignatureAsset::query()->create([
        'owner_user_id' => pklUser(['kepsek'])->getKey(),
        'file_path' => 'signature/ttd-baru.png',
        'is_active' => true,
        'uploaded_by' => $context['hubin']->getKey(),
    ]);

    app(IssueSuratTugasPembimbing::class)->handle($plotting, $context['kaprog']);

    expect($plotting->refresh()->suratTugas)->not->toBeNull()
        ->and($plotting->suratTugas->nomor_surat)->not->toBeNull();
});

test('visitasi validity starts at approval and documentation rules are enforced', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);
    $visitasi->refresh();

    $today = JakartaTime::today();
    $days = (int) config('pkl.visitasi.default_validity_days', 7);

    expect($visitasi->tanggal_mulai->toDateString())->toBe($today->toDateString())
        ->and($visitasi->tanggal_selesai->toDateString())->toBe($today->addDays($days - 1)->toDateString());

    $visitasi->update(['tanggal_selesai' => $today->toDateString()]);
    grantActiveRole('guru_pembimbing');
    $companyId = $context['company']->getKey();

    $documentation = app(UploadDokumentasi::class)->handle(
        $visitasi,
        $companyId,
        $today->toDateString(),
        UploadedFile::fake()->image('hari-ini.png'),
        $context['guru'],
        null,
    );

    expect($documentation)->toBeInstanceOf(VisitasiDokumentasi::class)
        ->and(fn () => app(UploadDokumentasi::class)->handle(
            $visitasi,
            $companyId,
            $today->toDateString(),
            UploadedFile::fake()->image('alion.gif'),
            $context['guru'],
            null,
        ))->toThrow(ValidationException::class)
        ->and(fn () => app(UploadDokumentasi::class)->handle(
            $visitasi,
            $companyId,
            $today->toDateString(),
            UploadedFile::fake()->image('besar.jpg')->size(6000),
            $context['guru'],
            null,
        ))->toThrow(ValidationException::class);

    $visitasi->update([
        'tanggal_mulai' => $today->subDays(2)->toDateString(),
        'tanggal_selesai' => $today->subDay()->toDateString(),
    ]);

    expect(fn () => app(UploadDokumentasi::class)->handle(
        $visitasi,
        $companyId,
        $today->toDateString(),
        UploadedFile::fake()->image('kedaluwarsa.jpg'),
        $context['guru'],
        null,
    ))->toThrow(ValidationException::class);
});

test('visitasi request for company outside guru plotting is rejected', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $other = Company::factory()->create();

    expect(fn () => app(RequestVisitasi::class)->handle(
        $context['guru'],
        [$other->getKey()],
        false,
        $context['kaprog'],
    ))->toThrow(ValidationException::class);
});

test('plotting page renders eligible companies for kaprog', function () {
    $context = plottingContext();
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->actingAs($context['kaprog'])->withSession(['active_role' => 'kaprog']);

    Livewire::test(PlottingGuru::class)
        ->assertOk()
        ->assertSee('Plotting Guru Pembimbing')
        ->assertSee($context['company']->nama);
});

test('visitasi page renders for kaprog and guru', function () {
    $context = plottingContext();
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->actingAs($context['kaprog'])->withSession(['active_role' => 'kaprog']);
    Livewire::test(VisitasiPage::class)->assertOk()->assertSee('Visitasi PKL');

    $this->actingAs($context['guru'])->withSession(['active_role' => 'guru_pembimbing']);
    Livewire::test(VisitasiPage::class)->assertOk();
});

test('hubin visitasi page renders', function () {
    $context = plottingContext();
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $this->actingAs($context['hubin'])->withSession(['active_role' => 'hubin']);

    Livewire::test(KelolaVisitasi::class)
        ->assertOk()
        ->assertSee('Pengelolaan Visitasi');
});

test('approve visitasi tanpa TTD kepsek tetap disetujui dan Hubin diberi tahu', function () {
    $context = plottingContext(withSignature: false);
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    Notification::fake();
    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);

    $visitasi->refresh();

    expect($visitasi->status)->toBe('disetujui')
        ->and($visitasi->nomor_surat)->toBeNull()
        ->and($visitasi->approved_at)->not->toBeNull();

    Notification::assertSentTo($context['hubin'], PklNotification::class);
});

test('penerbitan surat visitasi membekukan masa berlaku sejak hari terbit', function () {
    $context = plottingContext(withSignature: false);
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);
    expect($visitasi->refresh()->nomor_surat)->toBeNull();

    $this->travel(3)->days();

    Storage::disk('local')->put('signature/ttd-terbit.png', UploadedFile::fake()->image('ttd-terbit.png')->getContent());
    SignatureAsset::query()->create([
        'owner_user_id' => pklUser(['kepsek'])->getKey(),
        'file_path' => 'signature/ttd-terbit.png',
        'is_active' => true,
        'uploaded_by' => $context['hubin']->getKey(),
    ]);

    app(IssueSuratTugasVisitasi::class)->handle($visitasi->refresh(), $context['hubin']);
    $visitasi->refresh();

    $start = JakartaTime::today();

    expect($visitasi->nomor_surat)->not->toBeNull()
        ->and($visitasi->tanggal_mulai->toDateString())->toBe($start->toDateString())
        ->and($visitasi->tanggal_selesai->toDateString())->toBe($start->addDays(6)->toDateString())
        ->and(Storage::disk('local')->allFiles('documents/surat_tugas_visitasi'))->not->toBeEmpty();
});

test('menerbitkan ulang surat visitasi memakai nomor dan tanggal yang sama', function () {
    $context = plottingContext();
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);
    $visitasi->refresh();

    $nomor = $visitasi->nomor_surat;
    $mulai = $visitasi->tanggal_mulai->toDateString();

    $this->travel(2)->days();
    app(IssueSuratTugasVisitasi::class)->handle($visitasi->refresh(), $context['hubin']);
    $visitasi->refresh();

    expect($visitasi->nomor_surat)->toBe($nomor)
        ->and($visitasi->tanggal_mulai->toDateString())->toBe($mulai)
        ->and(DocumentQrToken::query()->where('document_type', 'surat_tugas_visitasi')->count())->toBe(1);
});

test('penerbitan dini hari memakai tanggal Jakarta bukan UTC', function () {
    $context = plottingContext(withSignature: false);
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);

    $this->travelTo(Carbon::parse('2026-11-20 02:30:00', 'Asia/Jakarta'));

    Storage::disk('local')->put('signature/ttd-subuh.png', UploadedFile::fake()->image('ttd-subuh.png')->getContent());
    SignatureAsset::query()->create([
        'owner_user_id' => pklUser(['kepsek'])->getKey(),
        'file_path' => 'signature/ttd-subuh.png',
        'is_active' => true,
        'uploaded_by' => $context['hubin']->getKey(),
    ]);

    app(IssueSuratTugasVisitasi::class)->handle($visitasi->refresh(), $context['hubin']);
    $visitasi->refresh();

    expect($visitasi->tanggal_mulai->toDateString())->toBe('2026-11-20')
        ->and($visitasi->tanggal_selesai->toDateString())->toBe('2026-11-26');
});

test('job kedaluwarsa mengabaikan visitasi tanpa nomor', function () {
    $context = plottingContext(withSignature: false);
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);
    $visitasi->update(['tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => '2026-11-02']);

    Cache::flush();
    ExpireVisitasiJob::dispatchSync();

    expect($visitasi->refresh()->status)->toBe('disetujui');
});

test('dokumentasi visitasi ditolak saat surat belum bernomor', function () {
    $context = plottingContext(withSignature: false);
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);

    grantActiveRole('guru_pembimbing');

    expect(fn () => app(UploadDokumentasi::class)->handle(
        $visitasi->refresh(),
        $context['company']->getKey(),
        JakartaTime::today()->toDateString(),
        UploadedFile::fake()->image('kunjungan.jpg'),
        $context['guru'],
        null,
    ))->toThrow(AuthorizationException::class);
});

test('tombol terbitkan surat hubin menerbitkan nomor setelah TTD tersedia', function () {
    $context = plottingContext(withSignature: false);
    grantActiveRole('kaprog');
    app(AssignGuru::class)->handle($context['company'], $context['period'], $context['guru'], $context['kaprog']);
    $visitasi = app(RequestVisitasi::class)->handle($context['guru'], [$context['company']->getKey()], false, $context['kaprog']);

    grantActiveRole('hubin');
    app(ApproveVisitasi::class)->handle($visitasi, $context['hubin']);

    Storage::disk('local')->put('signature/ttd-tombol.png', UploadedFile::fake()->image('ttd-tombol.png')->getContent());
    SignatureAsset::query()->create([
        'owner_user_id' => pklUser(['kepsek'])->getKey(),
        'file_path' => 'signature/ttd-tombol.png',
        'is_active' => true,
        'uploaded_by' => $context['hubin']->getKey(),
    ]);

    Filament::setCurrentPanel(Filament::getPanel('hubin'));
    $this->actingAs($context['hubin'])->withSession(['active_role' => 'hubin']);

    Livewire::test(KelolaVisitasi::class)
        ->call('issueLetter', $visitasi->getKey())
        ->assertOk();

    expect($visitasi->refresh()->nomor_surat)->not->toBeNull();
});
