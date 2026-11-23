<?php

use App\Actions\Form\CreateFormDefinition;
use App\Actions\Form\PublishFormDefinition;
use App\Actions\Form\SaveFormStructure;
use App\Actions\Sidang\ApproveUnlock;
use App\Actions\Sidang\IssueExaminerLink;
use App\Actions\Sidang\OpenSidangSubmission;
use App\Actions\Sidang\RequestUnlock;
use App\Actions\Sidang\SaveExternalExaminer;
use App\Actions\Sidang\ScheduleSidang;
use App\Actions\Sidang\SetKelulusan;
use App\Actions\Sidang\ShiftSidang;
use App\Actions\Sidang\SubmitScore;
use App\Enums\PengajuanStatus;
use App\Filament\Kaprog\Pages\JadwalSidangPage;
use App\Jobs\RelockScoreJob;
use App\Livewire\Eksternal\ExaminerEntry;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\ExternalExaminer;
use App\Models\ExternalSession;
use App\Models\FormDefinition;
use App\Models\JadwalSidang;
use App\Models\Kelas;
use App\Models\LaporanVersion;
use App\Models\NilaiSidangRevision;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\SidangResultsSummary;
use App\Models\User;
use App\Models\UserRole;
use App\Services\MagicLinkService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * @return array{kaprog: User, period: AcademicPeriod, pengajuan: PengajuanSiswa, siswa: User, examiner: ExternalExaminer, definition: FormDefinition}
 */
function sidangContext(bool $legit = true): array
{
    Notification::fake();

    $kaprog = pklUser(['kaprog']);
    $jurusanId = (int) $kaprog->roleScopeId('kaprog');
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusanId]);
    $siswa = User::factory()->siswa($jurusanId, (int) $kelas->getKey())->create();
    UserRole::query()->create(['user_id' => $siswa->getKey(), 'role' => 'siswa']);

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
        'snapshot_company_json' => ['nama' => $company->nama],
        'tanggal_mulai_pkl' => '2026-06-01',
        'tanggal_selesai_pkl' => '2026-12-31',
    ]);

    LaporanVersion::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'version_number' => 1,
        'file_path' => 'laporan/v1.pdf',
        'is_legit' => $legit,
        'created_by' => $kaprog->getKey(),
    ]);

    session(['active_role' => 'kaprog']);

    $examiner = app(SaveExternalExaminer::class)->handle(
        'Dewi Penguji',
        'email',
        'penguji@example.test',
        $kaprog,
    );

    $definition = app(CreateFormDefinition::class)->handle(
        $period,
        FormDefinition::TYPE_SIDANG,
        'Form Sidang',
        $kaprog,
    );
    $definition = app(SaveFormStructure::class)->handle($definition, [
        ['label' => 'Aspek Teknis', 'items' => [['label' => 'Penguasaan materi'], ['label' => 'Presentasi']]],
    ], $kaprog);
    app(PublishFormDefinition::class)->handle($definition, $kaprog);

    return compact('kaprog', 'period', 'pengajuan', 'siswa', 'examiner', 'definition');
}

function sidangSchedule(array $context, ?string $tanggal = '2026-12-07'): JadwalSidang
{
    return app(ScheduleSidang::class)->handle(
        $context['pengajuan'],
        $context['examiner'],
        $tanggal,
        $context['kaprog'],
    );
}

/** @return array{0: ExternalSession, 1: array<string, mixed>} */
function sidangLinkAndSession(JadwalSidang $jadwal, User $kaprog): array
{
    $issued = app(IssueExaminerLink::class)->handle($jadwal, $kaprog);
    $session = ExternalSession::query()->create([
        'magic_link_id' => $issued['link']->getKey(),
        'verified_at' => now(),
        'session_expires_at' => now()->addHours(24),
    ]);

    return [$session, $issued];
}

/** @return array<int, int> */
function sidangItemIds(FormDefinition $definition): array
{
    return $definition->items()->pluck('form_items.id')->all();
}

test('jadwal hanya untuk siswa dengan laporan sah dan tidak melewati akhir PKL', function () {
    $context = sidangContext();
    $context['pengajuan']->laporanVersions()->update(['is_legit' => false]);

    expect(fn () => sidangSchedule($context))->toThrow(ValidationException::class);

    $context['pengajuan']->laporanVersions()->update(['is_legit' => true]);

    expect(fn () => sidangSchedule($context, '2027-01-05'))->toThrow(ValidationException::class);

    $jadwal = sidangSchedule($context);

    expect($jadwal->status)->toBe(JadwalSidang::STATUS_TERJADWAL)
        ->and($jadwal->urutan)->toBe(1);

    expect(fn () => sidangSchedule($context))->toThrow(ValidationException::class);
});

/** Pengajuan + laporan sah tambahan untuk siswa baru pada periode yang sama. */
function sidangAddStudent(array $context, int $index): PengajuanSiswa
{
    $student = User::factory()->siswa(
        (int) $context['siswa']->jurusan_id,
        (int) $context['siswa']->kelas_id,
    )->create();
    UserRole::query()->create(['user_id' => $student->getKey(), 'role' => 'siswa']);

    $pengajuan = PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $context['pengajuan']->pengajuan_pkl_id,
        'company_id' => $context['pengajuan']->company_id,
        'academic_period_id' => $context['pengajuan']->academic_period_id,
        'siswa_id' => $student->getKey(),
        'status' => PengajuanStatus::SedangPkl->value,
        'snapshot_company_json' => ['nama' => $context['pengajuan']->snapshot_company_json['nama'] ?? '-'],
        'tanggal_mulai_pkl' => '2026-06-01',
        'tanggal_selesai_pkl' => '2026-12-31',
    ]);

    LaporanVersion::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'version_number' => 1,
        'file_path' => 'laporan/v'.$index.'.pdf',
        'is_legit' => true,
        'created_by' => $context['kaprog']->getKey(),
    ]);

    return $pengajuan;
}

test('shift memindahkan siswa dan menggeser antrean sisa ke hari kerja berikutnya', function () {
    $context = sidangContext();
    $first = sidangSchedule($context);

    $others = collect([2, 3])->map(fn (int $index): JadwalSidang => app(ScheduleSidang::class)->handle(
        sidangAddStudent($context, $index),
        $context['examiner'],
        '2026-12-07',
        $context['kaprog'],
    ));

    app(ShiftSidang::class)->handle($first, '2026-12-08', $context['kaprog']);

    expect($first->refresh()->tanggal->toDateString())->toBe('2026-12-08')
        ->and($first->urutan)->toBe(1)
        ->and($others[0]->refresh()->tanggal->toDateString())->toBe('2026-12-08')
        ->and($others[1]->refresh()->tanggal->toDateString())->toBe('2026-12-08');
});

test('shift ditolak utuh bila susunan baru melewati akhir PKL', function () {
    $context = sidangContext();
    $first = sidangSchedule($context);
    $other = app(ScheduleSidang::class)->handle(
        sidangAddStudent($context, 2),
        $context['examiner'],
        '2026-12-07',
        $context['kaprog'],
    );

    // Siswa kedua selesai PKL pada hari yang sama, jadi geseran ke 7 April ilegal.
    PengajuanSiswa::query()
        ->whereKey($other->pengajuan_siswa_id)
        ->update(['tanggal_selesai_pkl' => '2026-12-07']);

    expect(fn () => app(ShiftSidang::class)->handle($first, '2026-12-08', $context['kaprog']))
        ->toThrow(ValidationException::class);

    expect($first->refresh()->tanggal->toDateString())->toBe('2026-12-07')
        ->and($first->urutan)->toBe(1)
        ->and($other->refresh()->tanggal->toDateString())->toBe('2026-12-07')
        ->and($other->urutan)->toBe(2);
});

test('nilai dikunci sekali lalu revisi hanya lewat persetujuan Kaprog', function () {
    $context = sidangContext();
    $jadwal = sidangSchedule($context);
    [$session] = sidangLinkAndSession($jadwal, $context['kaprog']);
    $submission = app(OpenSidangSubmission::class)->handle($jadwal);
    $ids = sidangItemIds($context['definition']);

    app(SubmitScore::class)->handle($submission, [
        'items' => [$ids[0] => 90, $ids[1] => 80],
    ], $session);

    expect($submission->refresh()->is_submitted)->toBeTrue()
        ->and($submission->revision_count)->toBe(0)
        ->and($jadwal->refresh()->status)->toBe(JadwalSidang::STATUS_SELESAI)
        ->and(SidangResultsSummary::query()->where('form_submission_id', $submission->getKey())->exists())->toBeTrue();

    expect(fn () => app(SubmitScore::class)->handle($submission, [
        'items' => [$ids[0] => 70, $ids[1] => 70],
    ], $session))->toThrow(AuthorizationException::class);

    app(RequestUnlock::class)->handle($submission, $session, 'Nilai presentasi tertukar dengan siswa lain.');

    expect($submission->refresh()->pendingRevision())->not->toBeNull()
        ->and(NilaiSidangRevision::query()->where('status', 'menunggu_persetujuan')->count())->toBe(1);

    app(ApproveUnlock::class)->handle($submission->pendingRevision(), $context['kaprog']);

    expect($submission->refresh()->openRevision())->not->toBeNull();

    app(SubmitScore::class)->handle($submission, [
        'items' => [$ids[0] => 70, $ids[1] => 70],
    ], $session);

    expect($submission->refresh()->revision_count)->toBe(1)
        ->and($submission->openRevision())->toBeNull()
        ->and(NilaiSidangRevision::query()->value('status'))->toBe('selesai');
});

test('revisi yang lewat 24 jam dikunci ulang otomatis dan tautannya dicabut', function () {
    $context = sidangContext();
    $jadwal = sidangSchedule($context);
    [$session] = sidangLinkAndSession($jadwal, $context['kaprog']);
    $submission = app(OpenSidangSubmission::class)->handle($jadwal);
    $ids = sidangItemIds($context['definition']);

    app(SubmitScore::class)->handle($submission, ['items' => [$ids[0] => 90, $ids[1] => 80]], $session);
    app(RequestUnlock::class)->handle($submission, $session, 'Ingin memperbaiki nilai.');
    $result = app(ApproveUnlock::class)->handle($submission->pendingRevision(), $context['kaprog']);

    NilaiSidangRevision::query()->update(['unlock_expires_at' => now()->subHour()]);

    app(RelockScoreJob::class)->handle(app(MagicLinkService::class));

    expect(NilaiSidangRevision::query()->value('status'))->toBe('auto_relocked')
        ->and($result['link']->refresh()->is_active)->toBeFalse()
        ->and($submission->refresh()->openRevision())->toBeNull();
});

test('kelulusan diisi manual dan dipertahankan saat nilai direvisi', function () {
    $context = sidangContext();
    $jadwal = sidangSchedule($context);
    [$session] = sidangLinkAndSession($jadwal, $context['kaprog']);
    $submission = app(OpenSidangSubmission::class)->handle($jadwal);
    $ids = sidangItemIds($context['definition']);

    app(SubmitScore::class)->handle($submission, ['items' => [$ids[0] => 90, $ids[1] => 80]], $session);

    $summary = app(SetKelulusan::class)->handle($submission, 'Lulus', 'Pertahankan sikap.', $context['kaprog']);

    expect($summary->status_kelulusan)->toBe('Lulus')
        ->and($summary->catatan_kelulusan)->toBe('Pertahankan sikap.')
        ->and((float) $summary->nilai_total_per_kategori['Aspek Teknis'])->toBe(85.0);
});

test('membatalkan jadwal menghapus baris dan merapatkan urutan', function () {
    $context = sidangContext();
    $first = sidangSchedule($context);
    $second = app(ScheduleSidang::class)->handle(
        sidangAddStudent($context, 2),
        $context['examiner'],
        '2026-12-07',
        $context['kaprog'],
    );

    Filament::setCurrentPanel(Filament::getPanel('kaprog'));
    $this->actingAs($context['kaprog'])->withSession(['active_role' => 'kaprog']);

    Livewire::test(JadwalSidangPage::class)
        ->callAction(TestAction::make('cancel')->table($first))
        ->assertOk();

    expect(JadwalSidang::query()->find($first->getKey()))->toBeNull()
        ->and($second->refresh()->urutan)->toBe(1);
});

test('geser jadwal yang konflik ditolak dan konfliknya tampil di panel', function () {
    $context = sidangContext();
    $first = sidangSchedule($context);
    $other = app(ScheduleSidang::class)->handle(
        sidangAddStudent($context, 2),
        $context['examiner'],
        '2026-12-07',
        $context['kaprog'],
    );

    // Siswa kedua selesai PKL pada hari yang sama, jadi geseran ke 8 Desember ilegal.
    PengajuanSiswa::query()
        ->whereKey($other->pengajuan_siswa_id)
        ->update(['tanggal_selesai_pkl' => '2026-12-07']);

    Filament::setCurrentPanel(Filament::getPanel('kaprog'));
    $this->actingAs($context['kaprog'])->withSession(['active_role' => 'kaprog']);

    Livewire::test(JadwalSidangPage::class)
        ->callAction(TestAction::make('shift')->table($first), ['tanggal' => '2026-12-08'])
        ->assertNotified('Perubahan jadwal ditolak');

    expect($first->refresh()->tanggal->toDateString())->toBe('2026-12-07')
        ->and($first->urutan)->toBe(1)
        ->and($other->refresh()->tanggal->toDateString())->toBe('2026-12-07');
});

test('kaprog mencabut tautan penguji beserta sesinya', function () {
    $context = sidangContext();
    $jadwal = sidangSchedule($context);
    [$session, $issued] = sidangLinkAndSession($jadwal, $context['kaprog']);

    Filament::setCurrentPanel(Filament::getPanel('kaprog'));
    $this->actingAs($context['kaprog'])->withSession(['active_role' => 'kaprog']);

    Livewire::test(JadwalSidangPage::class)
        ->callAction(TestAction::make('revoke')->table($jadwal), ['reason' => 'Penguji yang ditetapkan keliru.'])
        ->assertOk();

    expect($issued['link']->refresh()->revoked_at)->not->toBeNull()
        ->and($session->refresh()->revoked_at)->not->toBeNull();
});

test('portal penguji menerima PIN dan menampilkan form nilai', function () {
    $context = sidangContext();
    $jadwal = sidangSchedule($context);
    $issued = app(IssueExaminerLink::class)->handle($jadwal, $context['kaprog']);

    $this->get('/eksternal/penguji/'.$issued['token'])->assertOk();

    $component = Livewire::test(ExaminerEntry::class, ['token' => $issued['token']])
        ->set('pin', $issued['pin'])
        ->call('verify');

    $component->assertRedirect(route('eksternal.penguji.nilai'));

    $sessionId = (int) ExternalSession::query()->latest('id')->value('id');

    $this->withSession(['external_session_id' => $sessionId])
        ->get('/eksternal/penguji-nilai')
        ->assertOk()
        ->assertSee('Aspek Teknis');
});
