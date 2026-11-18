<?php

use App\Services\CompanyNormalizer;
use Tests\TestCase;

uses(TestCase::class);

it('menormalkan email menjadi huruf kecil tanpa spasi tepi', function () {
    expect(CompanyNormalizer::email('  HRD@Contoh.CO.ID '))->toBe('hrd@contoh.co.id');
});

it('mengembalikan null untuk email kosong', function () {
    expect(CompanyNormalizer::email(null))->toBeNull()
        ->and(CompanyNormalizer::email(''))->toBeNull()
        ->and(CompanyNormalizer::email('   '))->toBeNull();
});

it('membuang skema, www, dan garis miring akhir pada website', function () {
    expect(CompanyNormalizer::website('https://www.Contoh.co.id/'))->toBe('contoh.co.id')
        ->and(CompanyNormalizer::website('http://contoh.co.id/profil/'))->toBe('contoh.co.id/profil')
        ->and(CompanyNormalizer::website('  WWW.Contoh.co.id  '))->toBe('contoh.co.id')
        ->and(CompanyNormalizer::website('contoh.co.id'))->toBe('contoh.co.id');
});

it('menyisakan hanya digit pada nomor telepon atau fax', function () {
    expect(CompanyNormalizer::telp('(021) 555-0001'))->toBe('0215550001')
        ->and(CompanyNormalizer::telp('+62 21 555 0001'))->toBe('62215550001')
        ->and(CompanyNormalizer::telp('021.555.0001'))->toBe('0215550001')
        ->and(CompanyNormalizer::telp('---'))->toBeNull()
        ->and(CompanyNormalizer::telp(null))->toBeNull();
});

it('menormalkan hanya kunci yang dikirim', function () {
    $data = CompanyNormalizer::attributes([
        'nama' => 'PT Contoh',
        'email' => ' HRD@Contoh.co.id ',
        'website' => 'https://www.contoh.co.id/',
    ]);

    expect($data)->toBe([
        'nama' => 'PT Contoh',
        'email' => 'hrd@contoh.co.id',
        'website' => 'contoh.co.id',
    ]);
});

it('tidak menyentuh nilai non-string', function () {
    $data = CompanyNormalizer::attributes(['telp_fax' => 12345]);

    expect($data['telp_fax'])->toBe(12345);
});
