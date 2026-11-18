<?php

use App\Actions\Auth\SwitchActiveRole;
use App\Livewire\Support\RoleSwitcher;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('memaksa peran yang tidak dimiliki ditolak 403', function () {
    $user = pklUser(['guru_pembimbing']);
    $this->actingAs($user);

    expect(fn () => app(SwitchActiveRole::class)->handle($user, 'kaprog'))
        ->toThrow(HttpException::class);
});

test('peran aktif yang tidak dimiliki menghasilkan 403 di panel', function () {
    $user = pklUser(['guru_pembimbing']);

    $this->actingAs($user)
        ->withSession(['active_role' => 'kaprog'])
        ->get('/app')
        ->assertForbidden();
});

test('pengguna dengan peran aktif sah dapat membuka panel', function () {
    $user = pklUser(['guru_pembimbing']);

    $this->actingAs($user)
        ->withSession(['active_role' => 'guru_pembimbing'])
        ->get('/app')
        ->assertOk();
});

test('pindah peran memperbarui sesi dan membersihkan state peran sebelumnya', function () {
    $user = pklUser(['walas', 'guru_pembimbing']);
    $this->actingAs($user);
    session(['active_role' => 'walas', 'role_filter' => 'rahasia-walas']);

    Livewire::test(RoleSwitcher::class)
        ->call('switchTo', 'guru_pembimbing')
        ->assertRedirect($user->panelHomeUrl('guru_pembimbing'));

    expect(session('active_role'))->toBe('guru_pembimbing')
        ->and(session('role_filter'))->toBeNull();
});

test('hak akses panel mengikuti peran yang dimiliki', function () {
    $hubin = pklUser(['hubin']);
    $guru = pklUser(['guru_pembimbing']);

    expect($guru->canAccessPanel(Filament::getPanel('app')))->toBeTrue()
        ->and($guru->canAccessPanel(Filament::getPanel('hubin')))->toBeFalse()
        ->and($hubin->canAccessPanel(Filament::getPanel('hubin')))->toBeTrue()
        ->and($hubin->canAccessPanel(Filament::getPanel('app')))->toBeFalse();
});

test('akun tanpa peran tidak bisa akses panel', function () {
    $user = pklUser([], ['is_first_login' => false]);

    $this->actingAs($user)
        ->withSession(['active_role' => 'guru_pembimbing'])
        ->get('/app')
        ->assertForbidden();
});
