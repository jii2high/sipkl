<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('membuat perusahaan dengan signer yang masih kosong', function () {
    $company = Company::factory()->create();

    expect($company->exists)->toBeTrue()
        ->and($company->source)->toBe('hubin')
        ->and($company->pembimbing_industri_nama)->toBeNull()
        ->and($company->pembimbing_industri_email_verified_at)->toBeNull()
        ->and($company->merged_into_id)->toBeNull();
});

it('menolak email perusahaan duplikat di level database (AC-12)', function () {
    Company::factory()->create(['email' => 'hrd@contoh.co.id']);

    expect(fn () => Company::factory()->create(['email' => 'hrd@contoh.co.id']))
        ->toThrow(QueryException::class);
});

it('menolak website dan telp_fax duplikat di level database', function () {
    Company::factory()->create(['website' => 'contoh.co.id', 'telp_fax' => '0215550001']);

    expect(fn () => Company::factory()->create(['website' => 'contoh.co.id']))
        ->toThrow(QueryException::class);
    expect(fn () => Company::factory()->create(['telp_fax' => '0215550001']))
        ->toThrow(QueryException::class);
});

it('mengizinkan banyak perusahaan tanpa email, website, dan telp', function () {
    Company::factory()->count(3)->create([
        'email' => null,
        'website' => null,
        'telp_fax' => null,
    ]);

    expect(Company::count())->toBe(3);
});

it('menolak pembimbing_industri_email_verified_at tanpa nama dan email signer', function () {
    expect(fn () => Company::factory()->create([
        'pembimbing_industri_email_verified_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => Company::factory()->create([
        'pembimbing_industri_nama' => 'Budi Santoso',
        'pembimbing_industri_email_verified_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('menolak kepala_industri_email_verified_at tanpa nama dan email signer', function () {
    expect(fn () => Company::factory()->create([
        'kepala_industri_email_verified_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('menerima verified_at bila nama dan email signer lengkap', function () {
    $company = Company::factory()->signerPembimbingTerverifikasi()->create();

    expect($company->fresh()->pembimbing_industri_email_verified_at)->not->toBeNull();
});

it('menolak source di luar siswa, hubin, import', function () {
    expect(fn () => Company::factory()->create(['source' => 'lainnya']))
        ->toThrow(QueryException::class);
});

it('menolak created_by yang tidak ada di tabel users', function () {
    expect(fn () => Company::factory()->create(['created_by' => 999999]))
        ->toThrow(QueryException::class);
});

it('mencatat pembuat dan perusahaan hasil merge ke target yang sah', function () {
    $hubin = User::factory()->create();
    $target = Company::factory()->create();
    $sumber = Company::factory()->sudahMergeKe($target)->dibuatOleh($hubin)->create();
    $sumber->refresh();

    expect($sumber->pembuat->id)->toBe($hubin->id)
        ->and($sumber->mergedInto->id)->toBe($target->id)
        ->and($target->mergedChildren()->count())->toBe(1)
        ->and($sumber->sudahMerge())->toBeTrue();
});

it('menolak merged_into_id yang menunjuk perusahaan tidak ada', function () {
    expect(fn () => Company::factory()->create(['merged_into_id' => 999999]))
        ->toThrow(QueryException::class);
});

it('scope belumMerge hanya mengembalikan perusahaan yang belum digabung', function () {
    $target = Company::factory()->create();
    Company::factory()->count(2)->create();
    Company::factory()->sudahMergeKe($target)->create();

    expect(Company::belumMerge()->count())->toBe(3)
        ->and(Company::count())->toBe(4);
});
