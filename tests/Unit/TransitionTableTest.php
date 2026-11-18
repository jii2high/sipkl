<?php

use App\Enums\Transitions\TransitionTable;
use App\Exceptions\InvalidTransition;

function transitionTable(): TransitionTable
{
    return new TransitionTable([
        'draft' => ['menunggu_verifikasi_kaprog' => ['siswa']],
        'menunggu_verifikasi_kaprog' => ['sedang_pkl' => ['*']],
    ]);
}

test('pasangan transisi tak dikenal ditolak dengan 422', function () {
    expect(fn () => transitionTable()->assert('draft', 'sedang_pkl'))
        ->toThrow(InvalidTransition::class, 'tidak dikenal');

    try {
        transitionTable()->assert('draft', 'sedang_pkl');
    } catch (InvalidTransition $exception) {
        expect($exception->httpStatus())->toBe(422);
    }
});

test('aktor yang tidak berhak ditolak dengan 409', function () {
    $table = transitionTable();

    expect($table->allows('draft', 'menunggu_verifikasi_kaprog', 'siswa'))->toBeTrue()
        ->and($table->allows('draft', 'menunggu_verifikasi_kaprog', 'walas'))->toBeFalse();

    try {
        $table->assert('draft', 'menunggu_verifikasi_kaprog', 'walas');
    } catch (InvalidTransition $exception) {
        expect($exception->httpStatus())->toBe(409);
    }
});

test('aktor wildcard mengizinkan semua peran dan aktor kosong', function () {
    expect(transitionTable()->allows('menunggu_verifikasi_kaprog', 'sedang_pkl', 'hubin'))->toBeTrue()
        ->and(transitionTable()->allows('menunggu_verifikasi_kaprog', 'sedang_pkl'))->toBeTrue()
        ->and(transitionTable()->allows('draft', 'menunggu_verifikasi_kaprog'))->toBeFalse();
});
