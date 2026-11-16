<?php

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function baseUserAttributes(array $overrides = []): array
{
    return array_merge([
        'nis_nip' => fake()->unique()->numerify('######'),
        'name' => 'Pengguna Uji',
        'email' => fake()->unique()->userName().'@smkn1kotabekasi.sch.id',
        'password' => 'rahasia-panjang',
        'user_type' => 'guru',
        'is_first_login' => true,
        'is_active' => true,
    ], $overrides);
}

it('menyimpan user dengan domain email sekolah', function () {
    $user = User::create(baseUserAttributes());

    expect($user->exists)->toBeTrue()
        ->and($user->is_first_login)->toBeTrue();
});

it('menolak email di luar domain sekolah', function () {
    expect(fn () => User::create(baseUserAttributes(['email' => 'guru@gmail.com'])))
        ->toThrow(QueryException::class);
});

it('menolak siswa tanpa jurusan dan kelas', function () {
    expect(fn () => User::create(baseUserAttributes(['user_type' => 'siswa'])))
        ->toThrow(QueryException::class);
});

it('menerima siswa yang punya jurusan dan kelas', function () {
    $jurusan = Jurusan::factory()->create();
    $kelas = Kelas::factory()->create(['jurusan_id' => $jurusan->id]);

    $siswa = User::create(baseUserAttributes([
        'user_type' => 'siswa',
        'jurusan_id' => $jurusan->id,
        'kelas_id' => $kelas->id,
    ]));

    expect($siswa->kelas->id)->toBe($kelas->id);
});

it('menolak role ganda dengan scope sama', function () {
    $user = User::factory()->create();

    UserRole::create(['user_id' => $user->id, 'role' => 'guru_pembimbing']);

    expect(fn () => UserRole::create(['user_id' => $user->id, 'role' => 'guru_pembimbing']))
        ->toThrow(QueryException::class);
});

it('mengizinkan role sama dengan scope jurusan berbeda', function () {
    $user = User::factory()->create();
    $rpl = Jurusan::factory()->create();
    $tkj = Jurusan::factory()->create();

    UserRole::create(['user_id' => $user->id, 'role' => 'kaprog', 'jurusan_id' => $rpl->id]);
    $kedua = UserRole::create(['user_id' => $user->id, 'role' => 'kaprog', 'jurusan_id' => $tkj->id]);
    $kedua->refresh();

    expect($kedua->exists)->toBeTrue()
        ->and($kedua->jurusan_key)->toBe($tkj->id);
});

it('menolak role kaprog tanpa jurusan dan walas tanpa kelas', function () {
    $user = User::factory()->create();

    expect(fn () => UserRole::create(['user_id' => $user->id, 'role' => 'kaprog']))
        ->toThrow(QueryException::class);
    expect(fn () => UserRole::create(['user_id' => $user->id, 'role' => 'walas']))
        ->toThrow(QueryException::class);
});
