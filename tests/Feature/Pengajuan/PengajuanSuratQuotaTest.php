<?php

use App\Actions\Pengajuan\AcceptByCompany;
use App\Actions\Pengajuan\ApproveByKaprog;
use App\Actions\Pengajuan\ApproveWithdrawal;
use App\Actions\Pengajuan\ConfirmPhysicalDocument;
use App\Actions\Pengajuan\IssueSuratPengantar;
use App\Actions\Pengajuan\RejectByCompany;
use App\Actions\Pengajuan\RequestWithdrawal;
use App\Actions\Pengajuan\SaveDraft;
use App\Actions\Pengajuan\SignByWalas;
use App\Actions\Pengajuan\SubmitPengajuan;
use App\Actions\Pengajuan\SubmitSuratBalasan;
use App\Enums\PengajuanStatus;
use App\Exceptions\DocumentIssuanceHeld;
use App\Exceptions\QuotaExceeded;
use App\Jobs\GroupCompanyJob;
use App\Jobs\ProvisionalReminderJob;
use App\Jobs\SendNotificationJob;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\SignatureAsset;
use App\Models\User;
use App\Models\UserRole;
use App\Services\NotificationService;
use App\Services\QuotaService;
use App\Support\JakartaTime;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();

    $this->jurusan = Jurusan::factory()->create();
    $this->kelas = Kelas::factory()->create(['jurusan_id' => $this->jurusan->id]);
    $this->period = AcademicPeriod::factory()->create();
    $this->company = Company::factory()->create(['nama' => 'PT Mitra Industri']);

    $this->siswa = pklUser(['siswa'], [
        'user_type' => 'siswa',
        'jurusan_id' => $this->jurusan->id,
        'kelas_id' => $this->kelas->id,
    ]);

    $this->kaprog = User::factory()->create();
    UserRole::query()->create([
        'user_id' => $this->kaprog->id,
        'role' => 'kaprog',
        'jurusan_id' => $this->jurusan->id,
    ]);

    $this->walas = User::factory()->create();
    UserRole::query()->create([
        'user_id' => $this->walas->id,
        'role' => 'walas',
        'kelas_id' => $this->kelas->id,
    ]);

    $this->hubin = pklUser(['hubin']);
});

function pklSuratGroup(User $siswa, AcademicPeriod $period, Company $company, User $kaprog, User $walas): PengajuanSiswa
{
    session(['active_role' => 'siswa']);
    $draft = app(SaveDraft::class)->handle($siswa, $period, ['company_id' => $company->id]);
    app(SubmitPengajuan::class)->handle($draft, $siswa);

    session(['active_role' => 'kaprog']);
    app(ApproveByKaprog::class)->handle($draft->refresh(), $kaprog);

    session(['active_role' => 'walas']);
    app(SignByWalas::class)->handle($draft->refresh(), $walas);

    (new GroupCompanyJob((int) $draft->id))->handle();

    return $draft->refresh();
}

function pklSuratAccepted(AcademicPeriod $period, Company $company, PengajuanPkl $group, User $siswa, bool $provisional = false): PengajuanSiswa
{
    return PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $group->id,
        'company_id' => $company->id,
        'academic_period_id' => $period->id,
        'siswa_id' => $siswa->id,
        'status' => PengajuanStatus::SedangPkl->value,
        'snapshot_company_json' => ['nama' => $company->nama],
        'is_provisional' => $provisional,
        'tanggal_mulai_pkl' => '2026-01-05',
        'tanggal_selesai_pkl' => '2026-04-30',
    ]);
}

function pklSuratPending(AcademicPeriod $period, Company $company, PengajuanPkl $group, User $siswa): PengajuanSiswa
{
    return PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $group->id,
        'company_id' => $company->id,
        'academic_period_id' => $period->id,
        'siswa_id' => $siswa->id,
        'status' => PengajuanStatus::MenungguBalasanPerusahaan->value,
        'snapshot_company_json' => ['nama' => $company->nama],
    ]);
}

test('hubin menerbitkan surat pengantar untuk group dan seluruh siswanya', function () {
    $pengajuan = pklSuratGroup($this->siswa, $this->period, $this->company, $this->kaprog, $this->walas);
    $group = PengajuanPkl::query()->findOrFail($pengajuan->pengajuan_pkl_id);

    SignatureAsset::query()->create([
        'owner_user_id' => $this->hubin->id,
        'file_path' => 'signature/ttd.png',
        'is_active' => true,
        'uploaded_by' => $this->hubin->id,
    ]);

    session(['active_role' => 'hubin']);

    $affected = app(IssueSuratPengantar::class)->handle($group, $this->hubin);

    $group->refresh();
    $pengajuan->refresh();

    expect($affected)->toBe(1)
        ->and($group->status)->toBe('surat_terbit')
        ->and($group->nomor_surat)->not->toBeNull()
        ->and($pengajuan->status)->toBe(PengajuanStatus::SuratPengantarTerbit->value);

    $nomor = $group->nomor_surat;

    expect(app(IssueSuratPengantar::class)->handle($group->refresh(), $this->hubin))->toBe(0)
        ->and($group->refresh()->nomor_surat)->toBe($nomor);
});

test('penerbitan surat ditahan tanpa scan TTD Kepsek', function () {
    $pengajuan = pklSuratGroup($this->siswa, $this->period, $this->company, $this->kaprog, $this->walas);
    $group = PengajuanPkl::query()->findOrFail($pengajuan->pengajuan_pkl_id);

    session(['active_role' => 'hubin']);

    expect(fn () => app(IssueSuratPengantar::class)->handle($group, $this->hubin))
        ->toThrow(DocumentIssuanceHeld::class);

    expect($group->refresh()->nomor_surat)->toBeNull()
        ->and($pengajuan->refresh()->status)->toBe(PengajuanStatus::MenungguSuratPengantar->value);
});

test('siswa mengunggah surat balasan setelah surat pengantar terbit', function () {
    $pengajuan = pklSuratGroup($this->siswa, $this->period, $this->company, $this->kaprog, $this->walas);
    $pengajuan->newQuery()->whereKey($pengajuan->id)->update([
        'status' => PengajuanStatus::SuratPengantarTerbit->value,
    ]);

    session(['active_role' => 'siswa']);

    $affected = app(SubmitSuratBalasan::class)->handle(
        $pengajuan->refresh(),
        $this->siswa,
        'surat-balasan/pt-mitra.pdf',
        'diterima',
    );

    $pengajuan->refresh();

    expect($affected)->toBe(1)
        ->and($pengajuan->status)->toBe(PengajuanStatus::MenungguBalasanPerusahaan->value)
        ->and($pengajuan->surat_balasan_path)->toBe('surat-balasan/pt-mitra.pdf')
        ->and($pengajuan->surat_balasan_hasil)->toBe('diterima')
        ->and($pengajuan->surat_balasan_uploaded_at)->not->toBeNull();
});

test('siswa lain dan berkas kosong ditolak saat unggah surat balasan', function () {
    $pengajuan = pklSuratGroup($this->siswa, $this->period, $this->company, $this->kaprog, $this->walas);
    $pengajuan->newQuery()->whereKey($pengajuan->id)->update([
        'status' => PengajuanStatus::SuratPengantarTerbit->value,
    ]);

    $lain = pklUser(['siswa'], ['user_type' => 'siswa', 'jurusan_id' => $this->jurusan->id, 'kelas_id' => $this->kelas->id]);

    session(['active_role' => 'siswa']);

    expect(fn () => app(SubmitSuratBalasan::class)->handle($pengajuan->refresh(), $lain, 'x.pdf', 'diterima'))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(SubmitSuratBalasan::class)->handle($pengajuan->refresh(), $this->siswa, '', 'diterima'))
        ->toThrow(ValidationException::class);

    expect($pengajuan->refresh()->status)->toBe(PengajuanStatus::SuratPengantarTerbit->value);
});

test('kaprog menerima siswa keenam dan menolak siswa ketujuh karena kuota', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    for ($i = 0; $i < 5; $i++) {
        pklSuratAccepted(
            $this->period,
            $this->company,
            $group,
            pklUser(['siswa'], ['user_type' => 'siswa', 'jurusan_id' => $this->jurusan->id, 'kelas_id' => $this->kelas->id]),
        );
    }

    $keenam = pklSuratPending($this->period, $this->company, $group, $this->siswa);

    session(['active_role' => 'kaprog']);

    expect(app(AcceptByCompany::class)->handle($keenam, $this->kaprog))->toBe(1);

    $keenam->refresh();

    expect($keenam->status)->toBe(PengajuanStatus::SedangPkl->value)
        ->and($keenam->is_provisional)->toBeTrue()
        ->and($keenam->tanggal_mulai_pkl?->toDateString())->toBe('2026-01-05');

    $ketujuh = pklSuratPending(
        $this->period,
        $this->company,
        $group,
        pklUser(['siswa'], ['user_type' => 'siswa', 'jurusan_id' => $this->jurusan->id, 'kelas_id' => $this->kelas->id]),
    );

    expect(fn () => app(AcceptByCompany::class)->handle($ketujuh, $this->kaprog))
        ->toThrow(QuotaExceeded::class);

    expect($ketujuh->refresh()->status)->toBe(PengajuanStatus::MenungguBalasanPerusahaan->value);
});

test('kaprog menerima dengan dokumen fisik dan tanggal ubahan', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    $pengajuan = pklSuratPending($this->period, $this->company, $group, $this->siswa);

    session(['active_role' => 'kaprog']);

    expect(app(AcceptByCompany::class)->handle($pengajuan, $this->kaprog, [
        'tanggal_mulai' => '2026-02-01',
        'tanggal_selesai' => '2026-03-01',
        'has_physical_document' => true,
    ]))->toBe(1);

    $pengajuan->refresh();

    expect($pengajuan->is_provisional)->toBeFalse()
        ->and($pengajuan->tanggal_mulai_pkl?->toDateString())->toBe('2026-02-01')
        ->and($pengajuan->tanggal_selesai_pkl?->toDateString())->toBe('2026-03-01');
});

test('kaprog menolak siswa setelah balasan dan slot tidak terpakai', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    $pengajuan = pklSuratPending($this->period, $this->company, $group, $this->siswa);

    session(['active_role' => 'kaprog']);

    expect(app(RejectByCompany::class)->handle($pengajuan, $this->kaprog, 'Perusahaan menolak jadwal.'))->toBe(1);

    expect($pengajuan->refresh()->status)->toBe(PengajuanStatus::Rejected->value);
});

test('hubin mengonfirmasi dokumen fisik dan status provisional selesai', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    $pengajuan = pklSuratAccepted($this->period, $this->company, $group, $this->siswa, true);

    session(['active_role' => 'hubin']);

    expect(app(ConfirmPhysicalDocument::class)->handle($pengajuan, $this->hubin))->toBe(1);

    $pengajuan->refresh();

    expect($pengajuan->is_provisional)->toBeFalse()
        ->and($pengajuan->dokumen_fisik_diterima_at)->not->toBeNull()
        ->and($pengajuan->dokumen_fisik_confirmed_by)->toBe($this->hubin->id);

    expect(app(ConfirmPhysicalDocument::class)->handle($pengajuan->refresh(), $this->hubin))->toBe(0);
});

test('selain hubin tidak dapat mengonfirmasi dokumen fisik', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    $pengajuan = pklSuratAccepted($this->period, $this->company, $group, $this->siswa, true);

    session(['active_role' => 'kaprog']);

    expect(fn () => app(ConfirmPhysicalDocument::class)->handle($pengajuan, $this->kaprog))
        ->toThrow(AuthorizationException::class);
});

test('penarikan diajukan siswa lalu disetujui kaprog dan melepas slot', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    $pengajuan = pklSuratAccepted($this->period, $this->company, $group, $this->siswa);

    session(['active_role' => 'siswa']);
    expect(app(RequestWithdrawal::class)->handle($pengajuan, $this->siswa, 'Alasan keluarga.'))->toBe(1);

    $pengajuan->refresh();
    expect($pengajuan->withdraw_requested_at)->not->toBeNull()
        ->and($pengajuan->status)->toBe(PengajuanStatus::SedangPkl->value);

    session(['active_role' => 'kaprog']);
    expect(app(ApproveWithdrawal::class)->handle($pengajuan, $this->kaprog))->toBe(1);

    expect($pengajuan->refresh()->status)->toBe(PengajuanStatus::Withdrawn->value)
        ->and(app(QuotaService::class)->used($this->company->id, $this->period->id))->toBe(0);
});

test('penarikan tanpa permintaan tidak dapat disetujui', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    $pengajuan = pklSuratAccepted($this->period, $this->company, $group, $this->siswa);

    session(['active_role' => 'kaprog']);

    expect(fn () => app(ApproveWithdrawal::class)->handle($pengajuan, $this->kaprog))
        ->toThrow(AuthorizationException::class);

    expect($pengajuan->refresh()->status)->toBe(PengajuanStatus::SedangPkl->value);
});

test('pengingat provisional hanya dikirim sekali per hari', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    $pengajuan = pklSuratAccepted($this->period, $this->company, $group, $this->siswa, true);

    $job = new ProvisionalReminderJob;
    $job->handle(app(NotificationService::class));
    $job->handle(app(NotificationService::class));

    Queue::assertPushed(SendNotificationJob::class, 1);

    expect($pengajuan->refresh()->last_provisional_reminder_at?->toDateString())
        ->toBe(JakartaTime::dateKey());
});

test('komponen kuota menampilkan jumlah terpakai dan batas', function () {
    $html = view('components.quota-bar', ['used' => 5, 'max' => 6])->render();

    expect($html)->toContain('5 dari 6')
        ->and($html)->toContain('aria-valuemax="6"');
});

test('halaman antrean Hubin dan konfirmasi fisik hanya untuk Hubin', function () {
    $this->actingAs($this->hubin)
        ->withSession(['active_role' => 'hubin'])
        ->get('/hubin/antrean-surat-pengantar')
        ->assertOk()
        ->assertSee('Antrean surat pengantar')
        ->assertSee('Tidak ada group menunggu surat');

    $this->actingAs($this->hubin)
        ->withSession(['active_role' => 'hubin'])
        ->get('/hubin/konfirmasi-dokumen-fisik')
        ->assertOk()
        ->assertSee('Konfirmasi dokumen fisik')
        ->assertSee('Tidak ada dokumen fisik tertunda');

    $this->actingAs($this->siswa)
        ->withSession(['active_role' => 'siswa'])
        ->get('/hubin/antrean-surat-pengantar')
        ->assertForbidden();
});

test('halaman siswa menampilkan unggah surat balasan setelah surat terbit', function () {
    $pengajuan = pklSuratGroup($this->siswa, $this->period, $this->company, $this->kaprog, $this->walas);
    $pengajuan->newQuery()->whereKey($pengajuan->id)->update([
        'status' => PengajuanStatus::SuratPengantarTerbit->value,
    ]);

    $this->actingAs($this->siswa)
        ->withSession(['active_role' => 'siswa'])
        ->get('/app/pengajuan-saya')
        ->assertOk()
        ->assertSee('Surat balasan perusahaan')
        ->assertSee('Unggah surat balasan');
});

test('antrean Kaprog menampilkan kuota untuk keputusan penerimaan', function () {
    $group = PengajuanPkl::query()->create([
        'company_id' => $this->company->id,
        'academic_period_id' => $this->period->id,
        'status' => 'terbentuk',
        'tahun_ajaran' => '2025/2026',
    ]);

    pklSuratPending($this->period, $this->company, $group, $this->siswa);

    $this->actingAs($this->kaprog)
        ->withSession(['active_role' => 'kaprog'])
        ->get('/kaprog/antrean-pengajuan')
        ->assertOk()
        ->assertSee('Kuota perusahaan')
        ->assertSee('0 dari 6');
});
