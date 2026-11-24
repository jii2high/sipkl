<?php

use App\Actions\Form\CreateFormDefinition;
use App\Actions\Form\PublishFormDefinition;
use App\Actions\Form\SaveFormStructure;
use App\Actions\Industri\GenerateIndustryLink;
use App\Actions\Industri\ReissueIndustryLink;
use App\Actions\Industri\RevokeIndustryLink;
use App\Actions\Industri\SubmitIndustryAssessment;
use App\Enums\PengajuanStatus;
use App\Filament\Guru\Pages\PenilaianIndustri as GuruPenilaianIndustri;
use App\Filament\Hubin\Pages\PenilaianIndustri;
use App\Jobs\IndustryAssessmentReminderJob;
use App\Jobs\SendNotificationJob;
use App\Livewire\Eksternal\IndustryAssessment;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\ExternalSession;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\GuruPembimbingPlotting;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\MagicLink;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\IndustryLinkNotification;
use App\Services\ExternalOtpService;
use App\Services\MagicLinkService;
use App\Services\NotificationService;
use App\Support\JakartaTime;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * @param  array<int, string>  $ends  tanggal_selesai_pkl per siswa aktif
 * @return array{kaprog: User, period: AcademicPeriod, company: Company, students: array<int, PengajuanSiswa>, guru: User, definition: FormDefinition}
 */
function industriContext(array $ends = ['2026-12-31', '2026-12-31']): array
{
    Notification::fake();

    $kaprog = pklUser(['kaprog']);
    $jurusanId = (int) $kaprog->roleScopeId('kaprog');
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusanId]);

    $period = AcademicPeriod::factory()->create();
    $company = Company::factory()->create([
        'pembimbing_industri_nama' => 'Budi Santoso',
        'pembimbing_industri_email' => 'budi@industri.test',
        'pembimbing_industri_email_verified_at' => now(),
    ]);

    $group = PengajuanPkl::query()->create([
        'company_id' => $company->getKey(),
        'academic_period_id' => $period->getKey(),
        'status' => 'berjalan',
    ]);

    $students = [];

    foreach ($ends as $end) {
        $user = User::factory()->siswa($jurusanId, (int) $kelas->getKey())->create();
        UserRole::query()->create(['user_id' => $user->getKey(), 'role' => 'siswa']);

        $students[] = PengajuanSiswa::query()->create([
            'pengajuan_pkl_id' => $group->getKey(),
            'company_id' => $company->getKey(),
            'academic_period_id' => $period->getKey(),
            'siswa_id' => $user->getKey(),
            'status' => PengajuanStatus::SedangPkl->value,
            'snapshot_company_json' => ['nama' => $company->nama],
            'tanggal_mulai_pkl' => '2026-06-01',
            'tanggal_selesai_pkl' => $end,
        ]);
    }

    $guru = pklUser(['guru_pembimbing']);
    GuruPembimbingPlotting::query()->create([
        'company_id' => $company->getKey(),
        'academic_period_id' => $period->getKey(),
        'guru_id' => $guru->getKey(),
        'plotted_by' => $kaprog->getKey(),
        'plotted_at' => now(),
    ]);

    session(['active_role' => 'kaprog']);

    $definition = app(CreateFormDefinition::class)->handle(
        $period,
        FormDefinition::TYPE_INDUSTRI,
        'Form Penilaian Industri',
        $kaprog,
        true,
        true,
    );

    $definition = app(SaveFormStructure::class)->handle($definition, [
        ['label' => 'Kedisiplinan', 'items' => [['label' => 'Kehadiran'], ['label' => 'Kerja sama']]],
    ], $kaprog);

    $definition = app(PublishFormDefinition::class)->handle($definition, $kaprog);

    session(['active_role' => 'guru_pembimbing']);

    return compact('kaprog', 'period', 'company', 'students', 'guru', 'definition');
}

function industriStudent(array $context, int $jurusanId, string $status): PengajuanSiswa
{
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusanId]);
    $user = User::factory()->siswa($jurusanId, (int) $kelas->getKey())->create();
    UserRole::query()->create(['user_id' => $user->getKey(), 'role' => 'siswa']);

    return PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $context['students'][0]->pengajuan_pkl_id,
        'company_id' => $context['company']->getKey(),
        'academic_period_id' => $context['period']->getKey(),
        'siswa_id' => $user->getKey(),
        'status' => $status,
        'snapshot_company_json' => ['nama' => $context['company']->nama],
        'tanggal_mulai_pkl' => '2026-06-01',
        'tanggal_selesai_pkl' => '2026-12-31',
    ]);
}

function industriTravel(string $date): void
{
    test()->travelTo(CarbonImmutable::parse($date.' 09:00', JakartaTime::TZ));
}

/** @return array{link: MagicLink, token: string, url: string} */
function industriIssue(array $context): array
{
    return app(GenerateIndustryLink::class)->handle(
        $context['company'],
        $context['period'],
        $context['definition'],
        $context['guru'],
    );
}

function industriSession(array $issued): ExternalSession
{
    return ExternalSession::query()->create([
        'magic_link_id' => $issued['link']->getKey(),
        'verified_at' => now(),
        'session_expires_at' => now()->addHours(24),
    ]);
}

/** @return array<string, mixed> */
function industriAnswers(FormDefinition $definition): array
{
    $items = [];

    foreach ($definition->items()->pluck('form_items.id') as $id) {
        $items[$id] = '90';
    }

    return [
        'items' => $items,
        'kehadiran' => ['sakit' => 0, 'izin' => 1, 'tanpa_keterangan' => 0],
        'catatan' => 'Disiplin dan kooperatif.',
    ];
}

test('tautan industri ditolak sebelum window H-1 bulan dibuka', function () {
    $context = industriContext();

    industriTravel('2026-11-01');

    expect(fn () => industriIssue($context))->toThrow(ValidationException::class);

    expect(FormSubmission::query()->count())->toBe(0);
});

test('tautan industri hanya mencakup siswa aktif pada jurusan form', function () {
    $context = industriContext();
    $withdrawn = industriStudent($context, (int) $context['students'][0]->siswa->jurusan_id, PengajuanStatus::Withdrawn->value);
    $otherJurusan = Jurusan::factory()->create();
    $other = industriStudent($context, (int) $otherJurusan->getKey(), PengajuanStatus::SedangPkl->value);

    industriTravel('2026-12-01');

    $issued = industriIssue($context);
    $submission = FormSubmission::query()->sole();

    $pivot = $submission->students()->pluck('pengajuan_siswa_id')->map(fn ($id): int => (int) $id)->all();

    expect($submission->form_type)->toBe(FormDefinition::TYPE_INDUSTRI)
        ->and((int) $submission->company_id)->toBe((int) $context['company']->getKey())
        ->and($pivot)->toBe(collect($context['students'])->pluck('id')->map(fn ($id): int => (int) $id)->all())
        ->and($pivot)->not->toContain($withdrawn->getKey())
        ->and($pivot)->not->toContain($other->getKey());

    expect($issued['link']->type)->toBe(MagicLinkService::TYPE_INDUSTRY_ASSESSMENT)
        ->and(app(MagicLinkService::class)->resolve($issued['token'])?->getKey())->toBe($issued['link']->getKey());

    Notification::assertSentOnDemand(IndustryLinkNotification::class);
});

test('guru lain tidak boleh menerbitkan tautan industri perusahaan itu', function () {
    $context = industriContext();
    $outsider = pklUser(['guru_pembimbing']);

    industriTravel('2026-12-01');

    expect(fn () => app(GenerateIndustryLink::class)->handle(
        $context['company'],
        $context['period'],
        $context['definition'],
        $outsider,
    ))->toThrow(AuthorizationException::class);
});

test('submit mengunci nilai dan menolak submit kedua maupun tautan baru', function () {
    $context = industriContext();
    industriTravel('2026-12-01');
    $issued = industriIssue($context);
    $session = industriSession($issued);
    $submission = FormSubmission::query()->sole();

    $unverified = ExternalSession::query()->create([
        'magic_link_id' => $issued['link']->getKey(),
        'otp_code_hash' => hash('sha256', '123456'),
        'otp_expires_at' => now()->addMinutes(5),
    ]);

    expect(fn () => app(SubmitIndustryAssessment::class)->handle(
        $submission,
        industriAnswers($context['definition']),
        $unverified,
    ))->toThrow(AuthorizationException::class);

    $locked = app(SubmitIndustryAssessment::class)->handle(
        $submission,
        industriAnswers($context['definition']),
        $session,
    );

    expect($locked->is_submitted)->toBeTrue()
        ->and($locked->nilai_total)->toBe('90.00')
        ->and($locked->jawaban['kehadiran'])->toEqual(['sakit' => 0, 'izin' => 1, 'tanpa_keterangan' => 0])
        ->and($locked->jawaban['catatan'])->toBe('Disiplin dan kooperatif.')
        ->and($locked->industryLinkState())->toBe('final');

    expect(fn () => app(SubmitIndustryAssessment::class)->handle(
        $locked,
        industriAnswers($context['definition']),
        $session,
    ))->toThrow(AuthorizationException::class);

    expect(fn () => industriIssue($context))->toThrow(ValidationException::class);
});

test('cabut tautan wajib alasan dan menutup sesi turunan', function () {
    $context = industriContext();
    industriTravel('2026-12-01');
    $issued = industriIssue($context);
    $session = industriSession($issued);
    $submission = FormSubmission::query()->sole();

    expect(fn () => app(RevokeIndustryLink::class)->handle(
        $submission,
        $context['guru'],
        'pendek',
    ))->toThrow(ValidationException::class);

    app(RevokeIndustryLink::class)->handle(
        $submission,
        $context['guru'],
        'Pembimbing industri minta tautan baru.',
    );

    expect($issued['link']->refresh()->is_active)->toBeFalse()
        ->and($issued['link']->revoked_at)->not->toBeNull()
        ->and($session->refresh()->revoked_at)->not->toBeNull()
        ->and($submission->refresh()->industryLinkState())->toBe('expired');

    // Override Hubin: tautan yang sudah kedaluwarsa/dicabut bisa diterbitkan lagi.
    $hubin = pklUser(['hubin']);
    session(['active_role' => 'hubin']);

    $new = app(GenerateIndustryLink::class)->handle(
        $context['company'],
        $context['period'],
        $context['definition'],
        $hubin,
    );

    expect($new['link']->is_active)->toBeTrue()
        ->and($submission->refresh()->industryLinkState())->toBe('active');
});

test('terbitkan ulang mencabut tautan lama dan mengirim tautan baru', function () {
    $context = industriContext();
    industriTravel('2026-12-01');
    $issued = industriIssue($context);
    $submission = FormSubmission::query()->sole();

    $new = app(ReissueIndustryLink::class)->handle(
        $submission,
        $context['guru'],
        'Tautan lama tidak sampai ke pembimbing.',
    );

    expect($issued['link']->refresh()->is_active)->toBeFalse()
        ->and($new['link']->is_active)->toBeTrue()
        ->and($new['link']->reissued_from_id)->toBe($issued['link']->getKey())
        ->and(app(MagicLinkService::class)->resolve($new['token']))->not->toBeNull();

    Notification::assertSentOnDemand(IndustryLinkNotification::class, 2);
});

test('pengingat H-7 sekali per tanggal dan berhenti setelah tautan dibuat', function () {
    Queue::fake();

    $context = industriContext();
    industriTravel('2026-12-24');

    $job = new IndustryAssessmentReminderJob;
    $job->handle(app(NotificationService::class));
    $job->handle(app(NotificationService::class));

    Queue::assertPushed(SendNotificationJob::class, 1);

    expect($context['company']->refresh()->last_industry_reminder_at?->toDateString())
        ->toBe('2026-12-24');

    Queue::fake();
    industriIssue($context);
    $job->handle(app(NotificationService::class));

    Queue::assertNotPushed(SendNotificationJob::class);
});

test('portal industri memuat siswa dan mengunci nilai lewat Livewire', function () {
    $context = industriContext();
    industriTravel('2026-12-01');
    $issued = industriIssue($context);

    $code = app(ExternalOtpService::class)->issue($issued['link'], 'budi@industri.test');
    $session = ExternalSession::query()->latest('id')->firstOrFail();
    app(ExternalOtpService::class)->verify($session, $code);
    request()->attributes->set('external_session', $session->refresh());

    $component = new IndustryAssessment;
    $component->mount(request());

    expect($component->students())->toHaveCount(2);

    foreach ($context['definition']->items()->pluck('form_items.id') as $id) {
        $component->items[$id] = '80';
    }

    $component->catatan = 'Baik';
    $component->submit();

    expect(FormSubmission::query()->sole()->is_submitted)->toBeTrue()
        ->and($component->notice)->toBe(__('pkl.industri.score_saved'));
});

test('halaman penilaian industri Hubin menampilkan tautan dan mencabutnya beralasan', function () {
    $context = industriContext();
    industriTravel('2026-12-01');
    industriIssue($context);

    $hubin = pklUser(['hubin']);
    Filament::setCurrentPanel(Filament::getPanel('hubin'));
    $this->actingAs($hubin)->withSession(['active_role' => 'hubin']);

    $submission = FormSubmission::query()->sole();

    Livewire::test(PenilaianIndustri::class)
        ->assertOk()
        ->assertSee($context['company']->nama)
        ->assertSee(__('pkl.industri.state_active'));

    Livewire::test(PenilaianIndustri::class)
        ->callAction(TestAction::make('revoke')->table($submission), ['reason' => 'pendek'])
        ->assertOk();

    expect($submission->refresh()->industryLinkState())->toBe('active');

    Livewire::test(PenilaianIndustri::class)
        ->callAction(TestAction::make('revoke')->table($submission), [
            'reason' => 'Pembimbing industri minta tautan baru.',
        ])
        ->assertOk();

    expect($submission->refresh()->industryLinkState())->toBe('expired');

    Livewire::test(PenilaianIndustri::class)
        ->assertActionVisible(TestAction::make('issue')->table($submission->refresh()));
});

test('halaman penilaian industri guru menerbitkan tautan dalam window', function () {
    $context = industriContext();
    $context['period']->update(['is_active' => true]);

    industriTravel('2026-12-01');

    Filament::setCurrentPanel(Filament::getPanel('guru'));
    $this->actingAs($context['guru'])->withSession(['active_role' => 'guru_pembimbing']);

    $rowKey = $context['company']->getKey().'|'.$context['definition']->getKey();

    Livewire::test(GuruPenilaianIndustri::class)
        ->assertOk()
        ->assertSee($context['company']->nama)
        ->assertSee(__('pkl.industri.state_none'))
        ->callAction(TestAction::make('issue')->table($rowKey))
        ->assertNotified(__('pkl.industri.link_issued'));

    expect(FormSubmission::query()->sole()->industryLinkState())->toBe('active');
});
