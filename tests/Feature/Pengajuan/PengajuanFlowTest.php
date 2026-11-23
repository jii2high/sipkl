<?php

use App\Actions\Pengajuan\ApproveByKaprog;
use App\Actions\Pengajuan\RejectByKaprog;
use App\Actions\Pengajuan\RejectByWalas;
use App\Actions\Pengajuan\ReopenPengajuan;
use App\Actions\Pengajuan\SaveDraft;
use App\Actions\Pengajuan\SignByWalas;
use App\Actions\Pengajuan\SubmitPengajuan;
use App\Enums\PengajuanStatus;
use App\Exceptions\InvalidTransition;
use App\Jobs\GroupCompanyJob;
use App\Livewire\Portal\PengajuanSiswa as PortalPengajuan;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Models\UserRole;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('kaprog'));

    $this->jurusan = Jurusan::factory()->create();
    $this->kelas = Kelas::factory()->create(['jurusan_id' => $this->jurusan->id]);
    $this->period = AcademicPeriod::factory()->create();
    $this->company = Company::factory()->create(['nama' => 'PT Contoh Awal']);

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
});

function buatDraftPkl(User $siswa, AcademicPeriod $period, Company $company): PengajuanSiswa
{
    session(['active_role' => 'siswa']);

    return app(SaveDraft::class)->handle($siswa, $period, ['company_id' => $company->id]);
}

function kirimPengajuanPkl(PengajuanSiswa $pengajuan, User $siswa): void
{
    session(['active_role' => 'siswa']);

    app(SubmitPengajuan::class)->handle($pengajuan, $siswa);
}

test('siswa menyimpan draf lalu mengirim dengan snapshot perusahaan', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);

    expect($draft->status)->toBe(PengajuanStatus::Draft->value)
        ->and($draft->snapshot_company_json)->toBeNull();

    kirimPengajuanPkl($draft, $this->siswa);

    $draft->refresh();

    expect($draft->status)->toBe(PengajuanStatus::MenungguVerifikasiKaprog->value)
        ->and($draft->snapshot_company_json['nama'])->toBe('PT Contoh Awal')
        ->and(Activity::query()->where('event', 'pengajuan_diajukan')->count())->toBe(1);
});

test('snapshot tidak berubah saat data perusahaan diedit setelah submit', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    $this->company->update(['nama' => 'PT Nama Baru', 'alamat' => 'Alamat baru']);

    expect($draft->refresh()->snapshot_company_json['nama'])->toBe('PT Contoh Awal');
});

test('submit ditolak saat periode terkunci', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    $this->period->update(['locked_for_new_submission' => true]);

    expect(fn () => kirimPengajuanPkl($draft, $this->siswa))
        ->toThrow(ValidationException::class);

    expect($draft->refresh()->status)->toBe(PengajuanStatus::Draft->value);
});

test('siswa tidak dapat membuat pengajuan aktif kedua pada periode yang sama', function () {
    buatDraftPkl($this->siswa, $this->period, $this->company);

    expect(fn () => app(SaveDraft::class)->handle(
        $this->siswa,
        $this->period,
        ['company_id' => Company::factory()->create()->id],
    ))->toThrow(ValidationException::class);

    expect(PengajuanSiswa::query()->where('siswa_id', $this->siswa->id)->count())->toBe(1);
});

test('kaprog dari jurusan lain ditolak 403', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    $lain = User::factory()->create();
    UserRole::query()->create([
        'user_id' => $lain->id,
        'role' => 'kaprog',
        'jurusan_id' => Jurusan::factory()->create()->id,
    ]);

    session(['active_role' => 'kaprog']);

    expect(fn () => app(ApproveByKaprog::class)->handle($draft->refresh(), $lain))
        ->toThrow(AuthorizationException::class);
});

test('kaprog menyetujui sekali dan panggilan kedua idempoten', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    session(['active_role' => 'kaprog']);

    $action = app(ApproveByKaprog::class);

    expect($action->handle($draft->refresh(), $this->kaprog))->toBe(1)
        ->and($action->handle($draft->refresh(), $this->kaprog))->toBe(0)
        ->and($draft->refresh()->status)->toBe(PengajuanStatus::MenungguTtdWalas->value)
        ->and(Activity::query()->where('event', 'disetujui_kaprog')->count())->toBe(1);
});

test('transisi dari status yang salah menghasilkan 409', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    session(['active_role' => 'kaprog']);
    app(ApproveByKaprog::class)->handle($draft->refresh(), $this->kaprog);

    session(['active_role' => 'siswa']);

    expect(fn () => kirimPengajuanPkl($draft->refresh(), $this->siswa))
        ->toThrow(InvalidTransition::class);
});

test('penolakan kaprog wajib catatan minimal sepuluh karakter', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    session(['active_role' => 'kaprog']);

    expect(fn () => app(RejectByKaprog::class)->handle($draft->refresh(), $this->kaprog, 'pendek'))
        ->toThrow(ValidationException::class)
        ->and($draft->refresh()->status)->toBe(PengajuanStatus::MenungguVerifikasiKaprog->value);
});

test('penolakan kaprog menyimpan alasan di audit dan dapat dibuka kembali', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    session(['active_role' => 'kaprog']);
    app(RejectByKaprog::class)->handle($draft->refresh(), $this->kaprog, 'Perusahaan belum relevan dengan jurusan.');

    $activity = Activity::query()->where('event', 'ditolak_kaprog')->firstOrFail();

    expect($draft->refresh()->status)->toBe(PengajuanStatus::DitolakKaprog->value)
        ->and(data_get($activity->properties, 'catatan'))->toBe('Perusahaan belum relevan dengan jurusan.');

    session(['active_role' => 'siswa']);
    app(ReopenPengajuan::class)->handle($draft->refresh(), $this->siswa);

    expect($draft->refresh()->status)->toBe(PengajuanStatus::Draft->value);

    kirimPengajuanPkl($draft->refresh(), $this->siswa);

    expect($draft->refresh()->status)->toBe(PengajuanStatus::MenungguVerifikasiKaprog->value);
});

test('walas dari kelas lain ditolak 403', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    session(['active_role' => 'kaprog']);
    app(ApproveByKaprog::class)->handle($draft->refresh(), $this->kaprog);

    $lain = User::factory()->create();
    UserRole::query()->create([
        'user_id' => $lain->id,
        'role' => 'walas',
        'kelas_id' => Kelas::factory()->create()->id,
    ]);

    session(['active_role' => 'walas']);

    expect(fn () => app(SignByWalas::class)->handle($draft->refresh(), $lain))
        ->toThrow(AuthorizationException::class);
});

test('walas menandatangani lalu job grouping memasukkan siswa ke group', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    session(['active_role' => 'kaprog']);
    app(ApproveByKaprog::class)->handle($draft->refresh(), $this->kaprog);

    Queue::fake();
    session(['active_role' => 'walas']);
    app(SignByWalas::class)->handle($draft->refresh(), $this->walas);

    Queue::assertPushed(GroupCompanyJob::class);
    expect($draft->refresh()->status)->toBe(PengajuanStatus::MenungguSuratPengantar->value);

    $job = new GroupCompanyJob((int) $draft->id);
    $job->handle();
    $job->handle();

    $draft->refresh();

    expect($draft->pengajuan_pkl_id)->not->toBeNull()
        ->and(PengajuanPkl::query()->count())->toBe(1)
        ->and(PengajuanPkl::query()->findOrFail($draft->pengajuan_pkl_id)->company_id)->toBe($this->company->id);
});

test('penolakan walas menyimpan catatan dan mengembalikan ke draf', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);
    kirimPengajuanPkl($draft, $this->siswa);

    session(['active_role' => 'kaprog']);
    app(ApproveByKaprog::class)->handle($draft->refresh(), $this->kaprog);

    session(['active_role' => 'walas']);
    app(RejectByWalas::class)->handle($draft->refresh(), $this->walas, 'Surat izin orang tua belum lengkap.');

    expect($draft->refresh()->status)->toBe(PengajuanStatus::DitolakWalas->value);

    session(['active_role' => 'siswa']);
    app(ReopenPengajuan::class)->handle($draft->refresh(), $this->siswa);

    expect($draft->refresh()->status)->toBe(PengajuanStatus::Draft->value);
});

test('siswa dapat mengajukan perusahaan baru dengan kontak ternormalisasi', function () {
    session(['active_role' => 'siswa']);

    $draft = app(SaveDraft::class)->handle($this->siswa, $this->period, [
        'company' => [
            'nama' => 'PT Baru',
            'alamat' => 'Jl. Baru 1',
            'email' => '  INFO@PT-Baru.co.id ',
            'telp_fax' => '(021) 123-456',
        ],
    ]);

    expect($draft->company->source)->toBe('siswa')
        ->and($draft->company->email)->toBe('info@pt-baru.co.id')
        ->and($draft->company->telp_fax)->toBe('021123456');
});

test('perusahaan baru dengan email duplikat ditolak', function () {
    session(['active_role' => 'siswa']);
    Company::factory()->create(['email' => 'info@pt-test.co.id']);

    expect(fn () => app(SaveDraft::class)->handle($this->siswa, $this->period, [
        'company' => [
            'nama' => 'PT Lain',
            'alamat' => 'Jl. Lain 2',
            'email' => ' INFO@PT-Test.co.id ',
        ],
    ]))->toThrow(ValidationException::class);

    expect(Company::query()->where('nama', 'PT Lain')->exists())->toBeFalse();
});

test('daftar pengajuan dibatasi scope peran aktif', function () {
    $draft = buatDraftPkl($this->siswa, $this->period, $this->company);

    $lainJurusan = Jurusan::factory()->create();
    $lainKelas = Kelas::factory()->create(['jurusan_id' => $lainJurusan->id]);
    $siswaLain = pklUser(['siswa'], [
        'user_type' => 'siswa',
        'jurusan_id' => $lainJurusan->id,
        'kelas_id' => $lainKelas->id,
    ]);

    $lain = buatDraftPkl($siswaLain, $this->period, $this->company);

    session(['active_role' => 'kaprog']);

    expect(PengajuanSiswa::query()->visibleTo($this->kaprog)->pluck('id')->all())->toBe([$draft->id]);

    session(['active_role' => 'walas']);

    expect(PengajuanSiswa::query()->visibleTo($this->walas)->pluck('id')->all())->toBe([$draft->id])
        ->and(PengajuanSiswa::query()->visibleTo($siswaLain)->count())->toBe(0);
});

test('halaman siswa dan antrean mengikuti peran aktif', function () {
    $this->actingAs($this->siswa)
        ->withSession(['active_role' => 'siswa'])
        ->get('/siswa')
        ->assertOk();

    $this->actingAs($this->kaprog)
        ->withSession(['active_role' => 'kaprog'])
        ->get('/kaprog/antrean-pengajuan')
        ->assertOk();

    $this->actingAs($this->siswa)
        ->withSession(['active_role' => 'siswa'])
        ->get('/kaprog/antrean-pengajuan')
        ->assertForbidden();
});

test('portal siswa menyimpan draf lalu mengirim lewat komponen Livewire', function () {
    session(['active_role' => 'siswa']);
    $this->actingAs($this->siswa);

    Livewire::test(PortalPengajuan::class)
        ->set('companyId', $this->company->id)
        ->call('saveDraft')
        ->assertHasNoErrors();

    $pengajuan = PengajuanSiswa::query()->where('siswa_id', $this->siswa->id)->firstOrFail();

    expect($pengajuan->status)->toBe(PengajuanStatus::Draft->value);

    Livewire::test(PortalPengajuan::class)
        ->call('submit')
        ->assertHasNoErrors();

    expect($pengajuan->refresh()->status)->toBe(PengajuanStatus::MenungguVerifikasiKaprog->value)
        ->and($pengajuan->snapshot_company_json['nama'])->toBe('PT Contoh Awal');
});
