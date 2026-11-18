<?php

use App\Services\AuditService;

test('kunci rahasia disunting secara rekursif', function () {
    $hasil = AuditService::redact([
        'password' => 'rahasia',
        'otp_code' => '123456',
        'pin' => '4321',
        'api_token' => 'abc',
        'nama' => 'Ahmad',
        'nested' => ['secret_key' => 'x', 'catatan' => 'aman'],
    ]);

    expect($hasil['password'])->toBe(AuditService::REDACTED)
        ->and($hasil['otp_code'])->toBe(AuditService::REDACTED)
        ->and($hasil['pin'])->toBe(AuditService::REDACTED)
        ->and($hasil['api_token'])->toBe(AuditService::REDACTED)
        ->and($hasil['nama'])->toBe('Ahmad')
        ->and($hasil['nested']['secret_key'])->toBe(AuditService::REDACTED)
        ->and($hasil['nested']['catatan'])->toBe('aman');
});
