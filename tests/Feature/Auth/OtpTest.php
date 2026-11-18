<?php

use App\Exceptions\OtpException;
use App\Models\OtpCode;
use App\Services\OtpService;

test('percobaan OTP keempat menginvalidasi kode', function () {
    $user = pklUser();
    $otp = app(OtpService::class);
    $benar = $otp->issue($user);
    $salah = $benar === '000000' ? '111111' : '000000';

    foreach (range(1, 3) as $ignored) {
        expect(fn () => $otp->verify($user, $salah))->toThrow(OtpException::class);
    }

    expect(fn () => $otp->verify($user, $salah))->toThrow(OtpException::class);

    expect(OtpCode::query()->latest('id')->first()->invalidated_at)->not->toBeNull();
});

test('OTP yang kedaluwarsa ditolak', function () {
    $user = pklUser();
    $otp = app(OtpService::class);
    $code = $otp->issue($user);

    $this->travel((int) config('pkl.otp.internal.ttl_seconds') + 1)->seconds();

    expect(fn () => $otp->verify($user, $code))->toThrow(OtpException::class);
});

test('resend keenam dalam 30 menit ditolak sampai cooldown selesai', function () {
    $user = pklUser();
    $otp = app(OtpService::class);

    // Satu kirim awal + 5 resend = 6 kirim diizinkan dalam jendela.
    foreach (range(1, 6) as $ignored) {
        $otp->issue($user);
    }

    expect(fn () => $otp->issue($user))->toThrow(OtpException::class);

    try {
        $otp->issue($user);
    } catch (OtpException $exception) {
        expect($exception->getMessage())->toContain('Tunggu');
    }
});

test('kode OTP tidak disimpan sebagai plaintext', function () {
    $user = pklUser();
    $code = app(OtpService::class)->issue($user);

    expect(OtpCode::query()->first()->code_hash)
        ->not->toBe($code)
        ->toBe(hash('sha256', $code));
});
