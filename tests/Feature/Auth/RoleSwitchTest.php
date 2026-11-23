<?php

use Filament\Facades\Filament;

test('pindah peran dari menu panel memperbarui sesi dan mengarahkan ke permukaan peran', function () {
    $user = pklUser(['walas', 'guru_pembimbing']);

    $this->actingAs($user)->withSession(['active_role' => 'walas', 'role_filter' => 'rahasia']);

    $this->post('/role', ['role' => 'guru_pembimbing'])->assertRedirect(url('/guru'));

    expect(session('active_role'))->toBe('guru_pembimbing')
        ->and(session('role_filter'))->toBeNull();
});

test('pindah ke peran yang tidak dimiliki ditolak 403', function () {
    $user = pklUser(['walas']);

    $this->actingAs($user)->withSession(['active_role' => 'walas']);

    $this->post('/role', ['role' => 'kepsek'])->assertForbidden();
});

test('peran di luar daftar ditolak validasi', function () {
    $user = pklUser(['walas']);

    $this->actingAs($user)->withSession(['active_role' => 'walas']);

    $this->post('/role', ['role' => 'direktur'])->assertSessionHasErrors('role');
});

test('menu akun panel hanya menampilkan peran lain yang dimiliki', function () {
    $user = pklUser(['walas', 'guru_pembimbing']);

    $this->actingAs($user)->withSession(['active_role' => 'walas']);

    $items = collect(Filament::getPanel('guru')->getUserMenuItems())
        ->keyBy(fn ($action): string => $action->getName());

    expect($items->has('switch-guru_pembimbing'))->toBeTrue()
        ->and($items->has('switch-walas'))->toBeFalse()
        ->and($items->has('switch-kepsek'))->toBeFalse();
});
