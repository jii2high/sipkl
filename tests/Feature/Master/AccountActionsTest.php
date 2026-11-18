<?php

use App\Actions\Master\ResetOtp;
use App\Actions\Master\SetAccountActive;
use App\Models\OtpCode;
use Illuminate\Support\Facades\DB;

test('reset OTP membatalkan kode aktif dan mengembalikan akun ke login pertama', function () {
    $hubin = pklUser(['hubin']);
    $user = pklUser(['guru_pembimbing'], ['is_first_login' => false]);

    $otp = OtpCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => hash('sha256', '123456'),
        'purpose' => 'first_login',
        'expires_at' => now()->addMinute(),
    ]);

    app(ResetOtp::class)->handle($user, $hubin);

    expect($otp->refresh()->invalidated_at)->not->toBeNull()
        ->and($user->refresh()->is_first_login)->toBeTrue();
});

test('reset OTP dua kali tidak menambah entri audit baru', function () {
    $hubin = pklUser(['hubin']);
    $user = pklUser(['guru_pembimbing'], ['is_first_login' => false]);

    app(ResetOtp::class)->handle($user, $hubin);
    app(ResetOtp::class)->handle($user->refresh(), $hubin);

    expect(DB::table('activity_log')->where('event', 'otp_reset')->count())->toBe(1);
});

test('nonaktifkan akun menghapus sesi dan membatalkan OTP', function () {
    $hubin = pklUser(['hubin']);
    $user = pklUser(['guru_pembimbing']);

    DB::table('sessions')->insert([
        'id' => 'sesi-uji',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => 'x',
        'last_activity' => now()->timestamp,
    ]);

    $otp = OtpCode::query()->create([
        'user_id' => $user->id,
        'code_hash' => hash('sha256', '123456'),
        'purpose' => 'first_login',
        'expires_at' => now()->addMinute(),
    ]);

    app(SetAccountActive::class)->handle($user, false, $hubin);

    expect($user->refresh()->is_active)->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and($otp->refresh()->invalidated_at)->not->toBeNull();
});

test('aktifkan kembali akun mengubah is_active tanpa menghapus data', function () {
    $hubin = pklUser(['hubin']);
    $user = pklUser(['guru_pembimbing'], ['is_active' => false]);

    app(SetAccountActive::class)->handle($user, true, $hubin);

    expect($user->refresh()->is_active)->toBeTrue();
});
