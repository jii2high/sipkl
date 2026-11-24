<?php

use App\Enums\BimbinganStatus;
use App\Enums\PengajuanPklStatus;
use App\Enums\PengajuanStatus;
use App\Enums\SignatureStatus;
use App\Support\StatusChip;

test('setiap status punya label, tone, dan ikon', function () {
    foreach (StatusChip::CHIPS as $status => [$label, $tone]) {
        expect($label)->not->toBe('')
            ->and($tone)->toBeIn(['success', 'warning', 'danger', 'info', 'neutral'])
            ->and(StatusChip::icon($status))->toStartWith('heroicon-');
    }
});

test('enum status meneruskan label, warna, dan ikon dari StatusChip', function () {
    $cases = [
        ...PengajuanStatus::cases(),
        ...PengajuanPklStatus::cases(),
        ...BimbinganStatus::cases(),
        ...SignatureStatus::cases(),
    ];

    foreach ($cases as $case) {
        expect($case->getLabel())->not->toBe('')
            ->and($case->getIcon())->toStartWith('heroicon-')
            ->and($case->getColor())->toBeIn(['success', 'warning', 'danger', 'info', 'gray']);
    }

    expect(PengajuanStatus::SedangPkl->getColor())->toBe('success')
        ->and(PengajuanStatus::SedangPkl->getLabel())->toBe('Sedang PKL')
        ->and(PengajuanStatus::SedangPkl->getIcon())->toBe('heroicon-o-check-circle')
        ->and(SignatureStatus::Pending->getColor())->toBe('warning')
        ->and(BimbinganStatus::Revisi->getColor())->toBe('warning')
        ->and(PengajuanPklStatus::Bubar->getColor())->toBe('gray');
});
