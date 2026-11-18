<?php

use App\Models\Company;
use App\Rules\CompanyUniqueStrict;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

function validasiKolom(string $column, ?string $value, ?int $ignoreId = null): Illuminate\Contracts\Validation\Validator
{
    return Validator::make(
        ['nilai' => $value],
        ['nilai' => [new CompanyUniqueStrict($column, $ignoreId)]],
    );
}

it('menolak email yang sudah dipakai perusahaan lain (AC-12)', function () {
    Company::factory()->create(['email' => 'hrd@contoh.co.id']);

    $validator = validasiKolom('email', ' HRD@Contoh.co.id ');

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('nilai'))->toBe('Email perusahaan ini sudah terdaftar.');
});

it('menolak website dan telepon yang sudah dipakai (dalam bentuk ternormalisasi)', function () {
    Company::factory()->create([
        'website' => 'contoh.co.id',
        'telp_fax' => '0215550001',
    ]);

    expect(validasiKolom('website', 'https://www.CONTOH.co.id/')->fails())->toBeTrue()
        ->and(validasiKolom('telp_fax', '(021) 555-0001')->fails())->toBeTrue();
});

it('tidak menolak nama dan alamat yang mirip (itu tugas soft-warning M2.3)', function () {
    Company::factory()->create([
        'nama' => 'CV Ajikyo',
        'alamat' => 'Jl. Merdeka No. 1, Bekasi',
        'email' => 'hrd@ajikyo.co.id',
    ]);

    $validator = Validator::make([
        'nama' => 'CV Ajikiyo',
        'alamat' => 'Jl. Merdeka No. 2, Bekasi',
        'email' => 'hrd@ajikiyo.co.id',
    ], [
        'email' => [new CompanyUniqueStrict('email')],
    ]);

    expect($validator->fails())->toBeFalse();
});

it('mengabaikan record milik sendiri saat update', function () {
    $company = Company::factory()->create(['email' => 'hrd@contoh.co.id']);

    expect(validasiKolom('email', 'hrd@contoh.co.id')->fails())->toBeTrue()
        ->and(validasiKolom('email', 'hrd@contoh.co.id', $company->id)->fails())->toBeFalse();
});

it('mengabaikan perusahaan yang sudah di-merge', function () {
    $target = Company::factory()->create(['email' => 'target@contoh.co.id']);
    Company::factory()->create([
        'email' => 'lama@contoh.co.id',
    ])->update(['merged_into_id' => $target->id]);

    expect(validasiKolom('email', 'lama@contoh.co.id')->fails())->toBeFalse();
});

it('mengizinkan nilai kosong karena kolomnya nullable', function () {
    expect(validasiKolom('email', null)->fails())->toBeFalse()
        ->and(validasiKolom('website', '   ')->fails())->toBeFalse()
        ->and(validasiKolom('telp_fax', '---')->fails())->toBeFalse();
});

it('menolak nama kolom yang tidak didukung', function () {
    expect(fn () => new CompanyUniqueStrict('nama'))
        ->toThrow(InvalidArgumentException::class);
});
