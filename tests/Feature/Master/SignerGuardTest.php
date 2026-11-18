<?php

use App\Support\SignerGuard;

test('signer dengan jabatan generik tidak dapat menerima OTP', function () {
    expect(SignerGuard::canReceiveOtp('HRD', 'hrd@pt.test', now()))->toBeFalse()
        ->and(SignerGuard::canReceiveOtp('Kepala Bagian', 'kabag@pt.test', now()))->toBeFalse()
        ->and(SignerGuard::canReceiveOtp('Admin', 'admin@pt.test', now()))->toBeFalse();
});

test('signer nama individu dengan email terverifikasi dapat menerima OTP', function () {
    expect(SignerGuard::canReceiveOtp('Budi Santoso', 'budi@pt.test', now()))->toBeTrue();
});

test('email yang belum terverifikasi tidak dapat menerima OTP', function () {
    expect(SignerGuard::canReceiveOtp('Budi Santoso', 'budi@pt.test', null))->toBeFalse()
        ->and(SignerGuard::canReceiveOtp('Budi Santoso', null, now()))->toBeFalse();
});
