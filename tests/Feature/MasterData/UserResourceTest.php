<?php

use App\Filament\Hubin\Resources\Users\Pages\CreateUser;
use App\Filament\Hubin\Resources\Users\Pages\EditUser;
use App\Filament\Hubin\Resources\Users\Pages\ListUsers;
use App\Filament\Hubin\Resources\Users\RelationManagers\RolesRelationManager;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function loginUserResourceSebagai(string $role): User
{
    $user = User::factory()->create();
    UserRole::create(['user_id' => $user->id, 'role' => $role]);

    test()->actingAs($user)->withSession(['active_role' => $role]);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel('hubin');
});

it('menampilkan daftar pengguna untuk hubin', function () {
    loginUserResourceSebagai('hubin');

    User::factory()->create(['name' => 'Guru Contoh']);

    $this->get('/hubin/users')
        ->assertOk()
        ->assertSee('Guru Contoh');
});

it('menolak daftar pengguna untuk role non-hubin', function () {
    loginUserResourceSebagai('guru_pembimbing');

    $this->get('/hubin/users')->assertForbidden();
});

it('membuat akun baru dengan password awal NIS/NIP dan wajib login awal', function () {
    loginUserResourceSebagai('hubin');

    $jurusan = Jurusan::factory()->create();
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusan->id]);

    livewire(CreateUser::class)
        ->fillForm([
            'nis_nip' => '123456',
            'name' => 'Siswa Baru',
            'email' => 'siswa.baru@smkn1kotabekasi.sch.id',
            'user_type' => 'siswa',
            'jurusan_id' => $jurusan->id,
            'kelas_id' => $kelas->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('nis_nip', '123456')->sole();

    expect($user->is_first_login)->toBeTrue()
        ->and(Hash::check('123456', $user->password))->toBeTrue();
});

it('menolak siswa tanpa jurusan dan kelas lewat form', function () {
    loginUserResourceSebagai('hubin');

    livewire(CreateUser::class)
        ->fillForm([
            'nis_nip' => '123456',
            'name' => 'Siswa Baru',
            'email' => 'siswa.baru@smkn1kotabekasi.sch.id',
            'user_type' => 'siswa',
            'jurusan_id' => null,
            'kelas_id' => null,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['jurusan_id', 'kelas_id']);

    expect(User::query()->where('nis_nip', '123456')->exists())->toBeFalse();
});

it('menolak email di luar domain sekolah lewat form', function () {
    loginUserResourceSebagai('hubin');

    livewire(CreateUser::class)
        ->fillForm([
            'nis_nip' => '123456',
            'name' => 'Siswa Baru',
            'email' => 'siswa@gmail.com',
            'user_type' => 'guru',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('mereset alur login awal lewat aksi tabel', function () {
    $hubin = loginUserResourceSebagai('hubin');

    $target = User::factory()->sudahGantiPassword()->create([
        'nis_nip' => '654321',
        'password' => 'password-baru',
    ]);

    livewire(ListUsers::class)
        ->callAction(TestAction::make('resetLogin')->table($target))
        ->assertNotified();

    $target->refresh();

    expect($target->is_first_login)->toBeTrue()
        ->and(Hash::check('654321', $target->password))->toBeTrue();

    $log = DB::table('activity_log')->where('description', 'user.first_login_direset')->sole();

    expect($log->subject_id)->toBe($target->id)
        ->and($log->causer_id)->toBe($hubin->id)
        ->and(json_decode($log->properties, true))->toBe(['aksi' => 'reset_first_login']);
});

it('menambahkan peran kaprog dengan jurusan lewat relation manager', function () {
    loginUserResourceSebagai('hubin');

    $target = User::factory()->create();
    $jurusan = Jurusan::factory()->create();

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'role' => 'kaprog',
            'jurusan_id' => $jurusan->id,
        ])
        ->assertHasNoFormErrors();

    expect($target->roles()->where('role', 'kaprog')->where('jurusan_id', $jurusan->id)->exists())->toBeTrue();
});

it('menolak peran kaprog tanpa jurusan lewat relation manager', function () {
    loginUserResourceSebagai('hubin');

    $target = User::factory()->create();

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'role' => 'kaprog',
            'jurusan_id' => null,
        ])
        ->assertHasFormErrors(['jurusan_id']);

    expect($target->roles()->where('role', 'kaprog')->exists())->toBeFalse();
});

it('menolak peran walas tanpa kelas lewat relation manager', function () {
    loginUserResourceSebagai('hubin');

    $target = User::factory()->create();

    livewire(RolesRelationManager::class, [
        'ownerRecord' => $target,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'role' => 'walas',
            'kelas_id' => null,
        ])
        ->assertHasFormErrors(['kelas_id']);

    expect($target->roles()->where('role', 'walas')->exists())->toBeFalse();
});
