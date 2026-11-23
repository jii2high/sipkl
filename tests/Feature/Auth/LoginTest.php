<?php

use App\Livewire\Auth\ForcePasswordChange;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\OtpChallenge;
use App\Models\OtpCode;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('email berdomain lain ditolak sebelum OTP', function () {
    $user = pklUser(['guru_pembimbing'], ['is_first_login' => true, 'password' => 'password']);
    Notification::fake();

    $component = Livewire::test(Login::class)
        ->set('email', 'nama@example.com')
        ->set('password', 'password')
        ->call('submit');

    $component->assertHasErrors('email');
    expect($component->errors()->first('email'))->toContain(config('pkl.school.email_domain'))
        ->and(OtpCode::query()->count())->toBe(0);

    Notification::assertNothingSent();
});

test('akun tidak ada, kata sandi salah, dan akun nonaktif memberi respons sama', function () {
    $aktif = pklUser(['guru_pembimbing'], ['password' => 'password']);
    $nonaktif = pklUser(['guru_pembimbing'], ['password' => 'password', 'is_active' => false]);

    $salahSandi = Livewire::test(Login::class)->set('email', $aktif->email)->set('password', 'keliru')->call('submit');
    $takAda = Livewire::test(Login::class)->set('email', 'hantu@smkn1kotabekasi.sch.id')->set('password', 'keliru')->call('submit');
    $nonaktifTest = Livewire::test(Login::class)->set('email', $nonaktif->email)->set('password', 'password')->call('submit');

    expect($salahSandi->errors()->first('email'))->toBe(__('pkl.email.not_found'))
        ->and($takAda->errors()->first('email'))->toBe(__('pkl.email.not_found'))
        ->and($nonaktifTest->errors()->first('email'))->toBe(__('pkl.email.not_found'));
});

test('login tanpa first-login langsung masuk dan menetapkan peran aktif', function () {
    $user = pklUser(['guru_pembimbing'], ['is_first_login' => false, 'password' => 'password']);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('submit')
        ->assertRedirect(url('/app'));

    expect(auth()->check())->toBeTrue()
        ->and(session('active_role'))->toBe('guru_pembimbing');
});

test('login pertama mengirim OTP dan mengarahkan ke tantangan OTP', function () {
    $user = pklUser(['guru_pembimbing'], ['is_first_login' => true, 'password' => 'password']);
    Notification::fake();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('submit')
        ->assertRedirect(route('login.otp'));

    Notification::assertSentTo($user, OtpNotification::class);
    expect(OtpCode::query()->count())->toBe(1)
        ->and(session('auth.pending_user_id'))->toBe($user->id);
});

test('login dikunci sementara setelah percobaan gagal berulang', function () {
    $user = pklUser(['guru_pembimbing'], ['is_first_login' => false, 'password' => 'password']);

    for ($attempt = 0; $attempt < (int) config('pkl.login.max_attempts'); $attempt++) {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'keliru')
            ->call('submit')
            ->assertHasErrors('email');
    }

    $blocked = Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('submit');

    $blocked->assertHasErrors('email');

    expect($blocked->errors()->first('email'))->toContain('Terlalu banyak percobaan masuk')
        ->and(auth()->check())->toBeFalse();
});

test('alur first-login lengkap: OTP lalu wajib ganti kata sandi sampai masuk', function () {
    $user = pklUser(['guru_pembimbing'], ['is_first_login' => true, 'password' => 'password']);
    Notification::fake();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('submit');

    $code = null;
    Notification::assertSentTo($user, OtpNotification::class, function (OtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    expect($code)->not->toBeNull();

    Livewire::test(OtpChallenge::class)
        ->set('code', $code)
        ->call('verify')
        ->assertRedirect(route('login.password'));

    Livewire::test(ForcePasswordChange::class)
        ->set('password', 'SandiBaru123')
        ->set('password_confirmation', 'SandiBaru123')
        ->call('submit')
        ->assertRedirect(url('/app'));

    expect(auth()->check())->toBeTrue()
        ->and($user->fresh()->is_first_login)->toBeFalse()
        ->and(session('active_role'))->toBe('guru_pembimbing');
});
