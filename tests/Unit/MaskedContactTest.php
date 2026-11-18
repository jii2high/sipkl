<?php

use App\Support\MaskedContact;

test('telepon disamarkan dengan awal dan akhir tetap terbaca', function () {
    expect(MaskedContact::phone('0812-3456-7890'))->toBe('0812********90')
        ->and(MaskedContact::phone('0812'))->toBe('0***')
        ->and(MaskedContact::phone(''))->toBeNull();
});

test('email disamarkan tanpa menyembunyikan domain', function () {
    expect(MaskedContact::email('budi.santoso@pt.test'))->toBe('bu**********@pt.test')
        ->and(MaskedContact::emailShort('budi.santoso@pt.test'))->toBe('bu***@pt.test')
        ->and(MaskedContact::email('bukan-email'))->toBeNull();
});
