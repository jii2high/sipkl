<?php

use App\Filament\Hubin\Resources\AcademicPeriods\Pages\CreateAcademicPeriod;
use App\Models\AcademicPeriod;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AcademicPeriodGuard;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function loginPeriodeSebagai(string $role): User
{
    $user = User::factory()->create();
    UserRole::create(['user_id' => $user->id, 'role' => $role]);

    test()->actingAs($user)->withSession(['active_role' => $role]);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel('hubin');
});

it('menampilkan daftar periode untuk hubin', function () {
    loginPeriodeSebagai('hubin');

    AcademicPeriod::factory()->create(['tahun_ajaran' => '2026/2027', 'batch' => '1']);

    $this->get('/hubin/academic-periods')
        ->assertOk()
        ->assertSee('2026/2027');
});

it('menolak daftar periode untuk role non-hubin', function () {
    loginPeriodeSebagai('guru_pembimbing');

    $this->get('/hubin/academic-periods')->assertForbidden();
});

it('membuat periode akademik baru', function () {
    loginPeriodeSebagai('hubin');

    livewire(CreateAcademicPeriod::class)
        ->fillForm([
            'tahun_ajaran' => '2026/2027',
            'batch' => '1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'is_active' => true,
            'locked_for_new_submission' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(AcademicPeriod::query()->where('tahun_ajaran', '2026/2027')->where('batch', '1')->exists())->toBeTrue();
});

it('menolak duplikat tahun ajaran dan batch', function () {
    loginPeriodeSebagai('hubin');

    AcademicPeriod::factory()->create(['tahun_ajaran' => '2026/2027', 'batch' => '1']);

    livewire(CreateAcademicPeriod::class)
        ->fillForm([
            'tahun_ajaran' => '2026/2027',
            'batch' => '1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'is_active' => true,
            'locked_for_new_submission' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['tahun_ajaran']);

    expect(AcademicPeriod::count())->toBe(1);
});

it('mengizinkan tahun ajaran sama dengan batch berbeda', function () {
    loginPeriodeSebagai('hubin');

    AcademicPeriod::factory()->create(['tahun_ajaran' => '2026/2027', 'batch' => '1']);

    livewire(CreateAcademicPeriod::class)
        ->fillForm([
            'tahun_ajaran' => '2026/2027',
            'batch' => '2',
            'tanggal_mulai' => '2027-01-01',
            'tanggal_selesai' => '2027-06-30',
            'is_active' => true,
            'locked_for_new_submission' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(AcademicPeriod::count())->toBe(2);
});

it('mengizinkan lebih dari satu periode aktif', function () {
    loginPeriodeSebagai('hubin');

    AcademicPeriod::factory()->count(2)->create(['is_active' => true]);

    expect(AcademicPeriod::query()->where('is_active', true)->count())->toBe(2);
});

it('menolak tanggal selesai sebelum tanggal mulai', function () {
    loginPeriodeSebagai('hubin');

    livewire(CreateAcademicPeriod::class)
        ->fillForm([
            'tahun_ajaran' => '2026/2027',
            'batch' => '1',
            'tanggal_mulai' => '2026-12-31',
            'tanggal_selesai' => '2026-07-01',
            'is_active' => true,
            'locked_for_new_submission' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['tanggal_selesai']);

    expect(AcademicPeriod::count())->toBe(0);
});

it('menolak format tahun ajaran yang tidak sesuai', function () {
    loginPeriodeSebagai('hubin');

    livewire(CreateAcademicPeriod::class)
        ->fillForm([
            'tahun_ajaran' => '2026-2027',
            'batch' => '1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'is_active' => true,
            'locked_for_new_submission' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['tahun_ajaran']);

    expect(AcademicPeriod::count())->toBe(0);
});

it('mengizinkan hapus periode yang belum punya data terkait', function () {
    loginPeriodeSebagai('hubin');

    $period = AcademicPeriod::factory()->create();

    expect(Gate::allows('delete', $period))->toBeTrue();

    $period->delete();

    expect(AcademicPeriod::count())->toBe(0);
});

it('menolak hapus periode yang sudah punya data terkait', function () {
    loginPeriodeSebagai('hubin');

    $this->app->instance(AcademicPeriodGuard::class, new AcademicPeriodGuard(['users' => 'nis_nip']));

    $period = AcademicPeriod::factory()->create();
    $user = User::factory()->create(['nis_nip' => (string) $period->id]);

    expect($user->nis_nip)->toBe((string) $period->id)
        ->and(app(AcademicPeriodGuard::class)->punyaDataTerkait($period))->toBeTrue()
        ->and(Gate::allows('delete', $period))->toBeFalse();

    expect(fn () => $period->delete())
        ->toThrow(ValidationException::class);

    expect(AcademicPeriod::count())->toBe(1);
});
