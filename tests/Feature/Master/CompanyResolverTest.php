<?php

use App\Models\Company;
use App\Services\CompanyResolver;

test('email duplikat setelah normalisasi ditolak', function () {
    Company::factory()->create(['email' => 'info@pt-test.co.id']);

    $check = app(CompanyResolver::class)->inspect([
        'nama' => 'PT Contoh',
        'email' => '  INFO@PT-Test.co.id ',
    ]);

    expect($check->hasStrictConflicts())->toBeTrue()
        ->and($check->strict)->toHaveKey('email')
        ->and($check->strict['email']->email)->toBe('info@pt-test.co.id');
});

test('website duplikat setelah normalisasi ditolak', function () {
    Company::factory()->create(['website' => 'pt-test.co.id']);

    $check = app(CompanyResolver::class)->inspect([
        'nama' => 'PT Contoh',
        'website' => 'HTTPS://WWW.PT-Test.co.id/',
    ]);

    expect($check->strict)->toHaveKey('website');
});

test('telp/faks duplikat setelah normalisasi ditolak', function () {
    Company::factory()->create(['telp_fax' => '0215551234']);

    $check = app(CompanyResolver::class)->inspect([
        'nama' => 'PT Contoh',
        'telp_fax' => '+62 21-555-1234',
    ]);

    expect($check->strict)->toHaveKey('telp_fax');
});

test('nama mirip hanya memunculkan peringatan, bukan penolakan', function () {
    Company::factory()->create(['nama' => 'PT Sinar Jaya Abadi', 'alamat' => 'Jl. Merdeka 1']);

    $check = app(CompanyResolver::class)->inspect([
        'nama' => 'PT Sinar Jaya Abad',
        'alamat' => 'Jl. Merdeka 2',
    ]);

    expect($check->hasStrictConflicts())->toBeFalse()
        ->and($check->hasWarnings())->toBeTrue();
});

test('perusahaan hasil merge tetap menahan kunci uniknya', function () {
    $target = Company::factory()->create();
    Company::factory()->create(['email' => 'lama@pt-test.co.id', 'merged_into_id' => $target->id]);

    $check = app(CompanyResolver::class)->inspect([
        'nama' => 'PT Baru',
        'email' => 'lama@pt-test.co.id',
    ]);

    expect($check->hasStrictConflicts())->toBeTrue()
        ->and($check->strict)->toHaveKey('email');
});

test('perusahaan hasil merge tidak lagi memunculkan peringatan kemiripan', function () {
    $target = Company::factory()->create();
    Company::factory()->create(['nama' => 'PT Sinar Jaya Abadi', 'merged_into_id' => $target->id]);

    $check = app(CompanyResolver::class)->inspect([
        'nama' => 'PT Sinar Jaya Abad',
        'alamat' => 'Jl. Merdeka 1',
    ]);

    expect($check->hasWarnings())->toBeFalse();
});

test('baris yang sedang disunting tidak dianggap bentrok dengan dirinya sendiri', function () {
    $company = Company::factory()->create(['email' => 'sama@pt-test.co.id']);

    $check = app(CompanyResolver::class)->inspect(
        ['nama' => $company->nama, 'email' => 'sama@pt-test.co.id'],
        $company->id,
    );

    expect($check->hasStrictConflicts())->toBeFalse();
});
