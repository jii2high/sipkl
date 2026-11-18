<?php

use App\Livewire\Auth\ForcePasswordChange;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function openPasswordPage(int $userId): void
{
    session(['auth.pending_user_id' => $userId, 'auth.otp_verified' => true]);
}

test('kata sandi kurang dari 8 karakter ditolak dengan pesan spesifik', function () {
    $user = pklUser([], ['is_first_login' => true, 'password' => 'password']);
    openPasswordPage($user->id);

    $component = Livewire::test(ForcePasswordChange::class)
        ->set('password', '1234567')
        ->set('password_confirmation', '1234567')
        ->call('submit');

    $component->assertHasErrors('password');
    expect($component->errors()->first('password'))->toContain((string) config('pkl.password.min_length'))
        ->and($user->fresh()->is_first_login)->toBeTrue();
});

test('kata sandi sama dengan NIS/NIP ditolak dengan pesan spesifik', function () {
    $user = pklUser([], ['nis_nip' => '197001011997011009', 'is_first_login' => true, 'password' => 'password']);
    openPasswordPage($user->id);

    $component = Livewire::test(ForcePasswordChange::class)
        ->set('password', '197001011997011009')
        ->set('password_confirmation', '197001011997011009')
        ->call('submit');

    $component->assertHasErrors('password');
    expect($component->errors()->first('password'))->toBe(__('pkl.password.same_as_nis_nip'))
        ->and($user->fresh()->is_first_login)->toBeTrue();
});

test('kata sandi baru yang sah menyimpan hash dan menandai bukan first-login', function () {
    $user = pklUser(['guru_pembimbing'], ['is_first_login' => true, 'password' => 'password']);
    openPasswordPage($user->id);

    Livewire::test(ForcePasswordChange::class)
        ->set('password', 'SandiBaru123')
        ->set('password_confirmation', 'SandiBaru123')
        ->call('submit')
        ->assertRedirect(url('/app'));

    $fresh = $user->fresh();

    expect($fresh->is_first_login)->toBeFalse()
        ->and($fresh->password)->not->toBe('SandiBaru123')
        ->and(Hash::check('SandiBaru123', $fresh->password))->toBeTrue();
});
