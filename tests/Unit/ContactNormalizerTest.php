<?php

use App\Support\ContactNormalizer;

test('email dinormalisasi huruf kecil tanpa spasi tepi', function () {
    expect(ContactNormalizer::email('  INFO@PT-Test.CO.ID '))->toBe('info@pt-test.co.id')
        ->and(ContactNormalizer::email('   '))->toBeNull()
        ->and(ContactNormalizer::email(null))->toBeNull();
});

test('website dinormalisasi tanpa skema, www, dan garis miring ujung', function () {
    expect(ContactNormalizer::website('HTTPS://WWW.PT-Test.co.id/'))->toBe('pt-test.co.id')
        ->and(ContactNormalizer::website('http://pt-test.co.id'))->toBe('pt-test.co.id')
        ->and(ContactNormalizer::website('pt-test.co.id/path/'))->toBe('pt-test.co.id/path')
        ->and(ContactNormalizer::website(''))->toBeNull();
});

test('telp/faks dinormalisasi ke digit dan awalan nol', function () {
    expect(ContactNormalizer::phone('(021) 555-1234'))->toBe('0215551234')
        ->and(ContactNormalizer::phone('+62 21 555 1234'))->toBe('0215551234')
        ->and(ContactNormalizer::phone('62-812-3456-7890'))->toBe('081234567890')
        ->and(ContactNormalizer::phone('abc'))->toBeNull();
});

test('dua bentuk kontak yang berbeda terdeteksi sama', function () {
    expect(ContactNormalizer::phone('021-555-1234'))->toBe(ContactNormalizer::phone('+62 21 555 1234'))
        ->and(ContactNormalizer::website('www.pt.test'))->toBe(ContactNormalizer::website('https://pt.test/'));
});
