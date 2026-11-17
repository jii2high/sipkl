<?php

use App\Livewire\Public\Login;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('login:ip:'.hash('sha256', '127.0.0.1'));
});

it('menampilkan halaman login responsif dengan form email dan password', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('name="viewport"', false)
        ->assertSee('type="email"', false)
        ->assertSee('autocomplete="current-password"', false)
        ->assertSee('max-width: 380px', false);
});

it('menolak domain email selain domain sekolah sebelum autentikasi', function () {
    Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'password-benar')
        ->call('submit')
        ->assertHasErrors(['email' => 'regex']);

    expect(Auth::guest())->toBeTrue()
        ->and(DB::table('otp_codes')->count())->toBe(0);
});

it('menolak NIS/NIP sebagai identifier email', function () {
    Livewire::test(Login::class)
        ->set('email', '123456')
        ->set('password', 'password-benar')
        ->call('submit')
        ->assertHasErrors('email');

    expect(Auth::guest())->toBeTrue()
        ->and(DB::table('otp_codes')->count())->toBe(0);
});

it('memberikan pesan kredensial generik untuk password salah dan akun yang tidak dikenal', function () {
    $known = User::factory()->create(['email' => 'known@smkn1kotabekasi.sch.id']);

    $knownResult = Livewire::test(Login::class)
        ->set('email', $known->email)
        ->set('password', 'salah')
        ->call('submit')
        ->assertHasErrors('credentials');

    RateLimiter::clear('login:account:'.hash('sha256', $known->email));

    $unknownResult = Livewire::test(Login::class)
        ->set('email', 'unknown@smkn1kotabekasi.sch.id')
        ->set('password', 'salah')
        ->call('submit')
        ->assertHasErrors('credentials');

    $genericError = 'Email atau password tidak sesuai.';

    $knownResult->assertSee($genericError);
    $unknownResult->assertSee($genericError);
});

it('menolak akun nonaktif dengan pesan kredensial generik', function () {
    $user = User::factory()->nonaktif()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', $user->nis_nip)
        ->call('submit')
        ->assertSee('Email atau password tidak sesuai.')
        ->assertHasErrors('credentials');

    expect(Auth::guest())->toBeTrue();
});

it('membatasi percobaan berulang untuk kombinasi account dan IP', function () {
    $user = User::factory()->create(['email' => 'limit@smkn1kotabekasi.sch.id']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'salah')
            ->call('submit')
            ->assertHasErrors('credentials');
    }

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'salah')
        ->call('submit')
        ->assertSee('Terlalu banyak percobaan masuk. Silakan coba lagi sebentar.');
});

it('membatasi percobaan untuk akun yang sama meski IP-nya berbeda', function () {
    $email = 'account-limit@smkn1kotabekasi.sch.id';
    $accountKey = 'login:account:'.hash('sha256', $email);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        RateLimiter::hit($accountKey, 60);
    }

    Livewire::test(Login::class)
        ->set('email', $email)
        ->set('password', 'salah')
        ->call('submit')
        ->assertSee('Terlalu banyak percobaan masuk. Silakan coba lagi sebentar.');
});

it('menerapkan batas IP lintas alamat email berbeda', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        Livewire::test(Login::class)
            ->set('email', "user{$attempt}@smkn1kotabekasi.sch.id")
            ->set('password', 'salah')
            ->call('submit')
            ->assertHasErrors('credentials');
    }

    Livewire::test(Login::class)
        ->set('email', 'user5@smkn1kotabekasi.sch.id')
        ->set('password', 'salah')
        ->call('submit')
        ->assertSee('Terlalu banyak percobaan masuk. Silakan coba lagi sebentar.');
});

it('mengirim first-login ke verifikasi OTP', function () {
    Queue::fake();
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', strtoupper($user->email))
        ->set('password', $user->nis_nip)
        ->call('submit')
        ->assertRedirect(route('verify-otp'))
        ->assertSet('password', '');

    expect(Auth::guest())->toBeTrue()
        ->and(session('otp_pending'))->toBeTrue()
        ->and(session('otp_email'))->toBe($user->email)
        ->and(DB::table('otp_codes')->count())->toBe(1);
});

it('authenticates a returning Hubin account and selects its role', function () {
    $user = User::factory()->hubin()->sudahGantiPassword()->create();
    UserRole::create(['user_id' => $user->id, 'role' => 'hubin']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', $user->nis_nip)
        ->call('submit')
        ->assertRedirect('/hubin')
        ->assertSet('password', '');

    expect(Auth::id())->toBe($user->id)
        ->and(session('active_role'))->toBe('hubin');
});
