<?php

use App\Filament\Hubin\Resources\Users\Pages\ManageUsers;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $this->hubin = pklUser(['hubin']);
    $this->actingAs($this->hubin)->withSession(['active_role' => 'hubin']);
});

test('hubin dapat membuka daftar akun', function () {
    pklUser(['guru_pembimbing'], ['name' => 'Guru Contoh']);

    $this->get('/hubin/users')
        ->assertOk()
        ->assertSee('Guru Contoh');
});

test('akun siswa dibuat dengan scope dan kata sandi awal NIS', function () {
    $jurusan = Jurusan::factory()->create();
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusan->id]);

    Livewire\Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'nis_nip' => '12345678',
            'name' => 'Siswa Baru',
            'email' => '12345678@smkn1kotabekasi.sch.id',
            'user_type' => 'siswa',
            'jurusan_id' => $jurusan->id,
            'kelas_id' => $kelas->id,
            'is_active' => true,
            'roles' => [['role' => 'siswa']],
        ])
        ->assertHasNoFormErrors();

    $user = User::query()->where('nis_nip', '12345678')->firstOrFail();

    expect(Hash::check('12345678', $user->password))->toBeTrue()
        ->and($user->is_first_login)->toBeTrue()
        ->and($user->jurusan_id)->toBe($jurusan->id)
        ->and(UserRole::query()->where('user_id', $user->id)->where('role', 'siswa')->exists())->toBeTrue();
});

test('email di luar domain sekolah ditolak', function () {
    Livewire\Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'nis_nip' => '87654321',
            'name' => 'Guru Luar',
            'email' => 'guru@gmail.com',
            'user_type' => 'guru',
            'is_active' => true,
        ])
        ->assertHasFormErrors(['email']);

    expect(User::query()->where('nis_nip', '87654321')->exists())->toBeFalse();
});

test('action reset OTP mengembalikan akun ke login pertama', function () {
    $account = pklUser(['guru_pembimbing'], ['is_first_login' => false]);

    Livewire\Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('resetOtp')->table($account));

    expect($account->refresh()->is_first_login)->toBeTrue();
});

test('action nonaktifkan mengubah is_active akun', function () {
    $account = pklUser(['guru_pembimbing'], ['is_active' => true]);

    Livewire\Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('toggleActive')->table($account));

    expect($account->refresh()->is_active)->toBeFalse();
});
