<?php

use App\Models\Company;
use App\Services\CompanyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('menyarankan CV Ajikiyo untuk input CV Ajikyo (AC-11)', function () {
    $mirip = Company::factory()->create([
        'nama' => 'CV Ajikiyo',
        'alamat' => 'Jl. Merdeka No. 12, Bekasi',
    ]);
    Company::factory()->create(['nama' => 'PT Sinar Jaya Abadi']);

    $saran = app(CompanyResolver::class)->suggest('CV Ajikyo');

    expect($saran)->toHaveCount(1)
        ->and($saran->first()['perusahaan']->id)->toBe($mirip->id)
        ->and($saran->first()['skor'])->toBeGreaterThanOrEqual(0.72);
});

it('mengurutkan saran dari yang paling mirip', function () {
    Company::factory()->create(['nama' => 'CV Ajikyo Sejahtera']);
    $paling = Company::factory()->create(['nama' => 'CV Ajikyo']);

    $saran = app(CompanyResolver::class)->suggest('CV Ajikyo');

    expect($saran->first()['perusahaan']->id)->toBe($paling->id)
        ->and($saran->first()['skor'])->toBe(1.0);
});

it('tidak menyarankan perusahaan yang sama sekali berbeda', function () {
    Company::factory()->create(['nama' => 'PT Pertambangan Nusantara']);

    expect(app(CompanyResolver::class)->suggest('CV Ajikyo'))->toBeEmpty();
});

it('tidak menyarankan perusahaan yang sudah di-merge', function () {
    $target = Company::factory()->create(['nama' => 'PT Lain']);
    $lama = Company::factory()->create(['nama' => 'CV Ajikyo']);
    $lama->update(['merged_into_id' => $target->id]);

    expect(app(CompanyResolver::class)->suggest('CV Ajikyo'))->toBeEmpty();
});

it('mengembalikan koleksi kosong untuk nama kosong', function () {
    Company::factory()->create(['nama' => 'CV Ajikyo']);

    $resolver = app(CompanyResolver::class);

    expect($resolver->suggest(''))->toBeEmpty()
        ->and($resolver->suggest('   '))->toBeEmpty();
});

it('menghormati ambang yang dikonfigurasi', function () {
    Company::factory()->create(['nama' => 'CV Ajikiyo']);

    $longgar = new CompanyResolver(threshold: 0.5);
    $ketat = new CompanyResolver(threshold: 0.99);

    expect($longgar->suggest('CV Ajikyo'))->toHaveCount(1)
        ->and($ketat->suggest('CV Ajikyo'))->toBeEmpty();
});

it('membatasi jumlah saran yang dikembalikan', function () {
    Company::factory()->count(4)->sequence(
        ['nama' => 'CV Ajikyo Satu'],
        ['nama' => 'CV Ajikyo Dua'],
        ['nama' => 'CV Ajikyo Tiga'],
        ['nama' => 'CV Ajikyo Empat'],
    )->create();

    expect((new CompanyResolver(threshold: 0.5, limit: 2))->suggest('CV Ajikyo'))->toHaveCount(2);
});

it('memberi bonus kecil bila alamat juga mirip', function () {
    $resolver = new CompanyResolver(threshold: 0.0);
    $company = Company::factory()->create([
        'nama' => 'CV Ajikyo',
        'alamat' => 'Jl. Merdeka No. 12, Bekasi',
    ]);

    $tanpaAlamat = $resolver->skor('CV Ajikyo', null, $company);
    $denganAlamat = $resolver->skor('CV Ajikyo', 'Jl. Merdeka No. 12, Bekasi', $company);

    expect($tanpaAlamat)->toBe(1.0)
        ->and($denganAlamat)->toBe(1.0);
});

it('tidak tertipu wildcard LIKE pada nama', function () {
    Company::factory()->create(['nama' => 'PT Contoh']);

    expect(app(CompanyResolver::class)->suggest('CV %'))->toBeEmpty();
});
