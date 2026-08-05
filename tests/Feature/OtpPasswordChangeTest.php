<?php

use App\Mail\OtpPasswordChangeMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

function buatOtp(User $user, string $code = '123456'): OtpCode
{
    return OtpCode::create([
        'user_id' => $user->id,
        'code_hash' => Hash::make($code),
        'expires_at' => now()->addMinutes(10),
    ]);
}

test('ganti password first-login tanpa OTP diarahkan ke halaman verifikasi', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->from('/auth/force-change-password')
        ->post(route('password.force-change.update'), [
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])
        ->assertRedirect(route('password.force-change'))
        ->assertSessionHas('otp_redirect_to', route('password.force-change'));
});

test('user non first-login tanpa OTP diarahkan balik ke halaman security setelah verify', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($user)->from('/settings/security')
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])
        ->assertRedirect(route('password.force-change'))
        ->assertSessionHas('otp_redirect_to', route('security.edit'));
});

test('session flag OTP yang sudah lewat 10 menit dipaksa verifikasi ulang', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->withSession(['password_otp_verified_at' => now()->subMinutes(11)])
        ->from('/auth/force-change-password')
        ->post(route('password.force-change.update'), [
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])
        ->assertRedirect(route('password.force-change'));
});

test('request OTP mengirim email dan menyimpan kode ter-hash', function () {
    Mail::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->from('/auth/force-change-password')
        ->post(route('password.otp.request'))
        ->assertSessionHas('status');

    Mail::assertSent(OtpPasswordChangeMail::class, fn (OtpPasswordChangeMail $mail) => $mail->hasTo($user->email));

    $otp = OtpCode::where('user_id', $user->id)->first();

    expect($otp)->not->toBeNull()
        ->and($otp->code_hash)->not->toBe('123456')
        ->and($otp->expires_at->isFuture())->toBeTrue()
        ->and($otp->verified_at)->toBeNull();
});

test('request OTP hanya boleh 3x dalam 10 menit', function () {
    Mail::fake();
    $user = User::factory()->create();

    foreach (range(1, 3) as $i) {
        $this->actingAs($user)->from('/auth/force-change-password')
            ->post(route('password.otp.request'))
            ->assertSessionHas('status');
    }

    $this->actingAs($user)->from('/auth/force-change-password')
        ->post(route('password.otp.request'))
        ->assertSessionHasErrors(['otp']);
});

test('verify menolak kode salah dan menaikkan attempts', function () {
    $user = User::factory()->create();
    buatOtp($user);

    $this->actingAs($user)->from('/auth/force-change-password')
        ->post(route('password.otp.verify'), ['code' => '999999'])
        ->assertSessionHasErrors(['code']);

    expect(OtpCode::first()->attempts)->toBe(1);
});

test('verify menolak kode kedaluwarsa', function () {
    $user = User::factory()->create();
    OtpCode::create([
        'user_id' => $user->id,
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->subMinutes(1),
    ]);

    $this->actingAs($user)->from('/auth/force-change-password')
        ->post(route('password.otp.verify'), ['code' => '123456'])
        ->assertSessionHasErrors(['code']);
});

test('verify kode benar membuka form ganti password dan flag ter-clear setelahnya', function () {
    $user = User::factory()->create(['must_change_password' => true]);
    buatOtp($user);

    $this->actingAs($user)->from('/auth/force-change-password')
        ->post(route('password.otp.verify'), ['code' => '123456'])
        ->assertRedirect('/auth/force-change-password');

    $this->assertNotNull(session('password_otp_verified_at'));

    $this->post(route('password.force-change.update'), [
        'password' => 'passwordbaru',
        'password_confirmation' => 'passwordbaru',
    ])->assertRedirect(route('dashboard'));

    expect($user->fresh()->must_change_password)->toBeFalse()
        ->and(session('password_otp_verified_at'))->toBeNull();
});

test('kode OTP tidak bisa dipakai ulang setelah verified', function () {
    $user = User::factory()->create();
    buatOtp($user);

    $this->actingAs($user)->post(route('password.otp.verify'), ['code' => '123456'])
        ->assertSessionHasNoErrors();

    $this->from('/auth/force-change-password')
        ->post(route('password.otp.verify'), ['code' => '123456'])
        ->assertSessionHasErrors(['code']);
});

test('mailable otp memakai view dan subject yang benar', function () {
    $mail = new OtpPasswordChangeMail('123456');

    expect($mail->content()->view)->toBe('emails.otp-password-change')
        ->and($mail->envelope()->subject)->toBe('Kode OTP Ganti Password — SI-PKL');
});
