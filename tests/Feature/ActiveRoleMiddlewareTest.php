<?php

use App\Models\Jurusan;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ActiveRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['web', 'role:kaprog'])
        ->get('/uji/kaprog', fn () => 'kaprog-ok');

    Route::middleware(['web', 'role:kaprog,guru_pembimbing'])
        ->get('/uji/multi', fn () => 'multi-ok');
});

function userMultiRole(): User
{
    $user = User::factory()->create();
    $jurusan = Jurusan::factory()->create();

    UserRole::create(['user_id' => $user->id, 'role' => 'kaprog', 'jurusan_id' => $jurusan->id]);
    UserRole::create(['user_id' => $user->id, 'role' => 'guru_pembimbing']);

    return $user;
}

it('menolak 403 saat active_role guru_pembimbing mengakses route kaprog (AC-06)', function () {
    $user = userMultiRole();

    $this->actingAs($user)->withSession(['active_role' => 'guru_pembimbing'])
        ->get('/uji/kaprog')->assertForbidden();
});

it('mengizinkan route kaprog saat active_role kaprog', function () {
    $user = userMultiRole();

    $this->actingAs($user)->withSession(['active_role' => 'kaprog'])
        ->get('/uji/kaprog')->assertOk()->assertSee('kaprog-ok');
});

it('mengizinkan beberapa role sekaligus pada satu route', function () {
    $user = userMultiRole();

    $this->actingAs($user)->withSession(['active_role' => 'guru_pembimbing'])
        ->get('/uji/multi')->assertOk();

    $this->actingAs($user)->withSession(['active_role' => 'kaprog'])
        ->get('/uji/multi')->assertOk();
});

it('menolak bila active_role tidak dimiliki user', function () {
    $user = User::factory()->create();
    UserRole::create(['user_id' => $user->id, 'role' => 'guru_pembimbing']);

    $this->actingAs($user)->withSession(['active_role' => 'kaprog'])
        ->get('/uji/multi')->assertForbidden();
});

it('menolak user nonaktif walau role aktif sah', function () {
    $user = userMultiRole();
    $user->update(['is_active' => false]);

    $this->actingAs($user)->withSession(['active_role' => 'kaprog'])
        ->get('/uji/kaprog')->assertForbidden();
});

it('mengembalikan nama role dan scope objek dari role aktif', function () {
    $user = userMultiRole();
    $kaprog = UserRole::where('user_id', $user->id)->where('role', 'kaprog')->firstOrFail();

    $this->actingAs($user)->withSession(['active_role' => 'kaprog']);

    $active = app(ActiveRole::class);

    expect($active->name())->toBe('kaprog')
        ->and($active->isValid())->toBeTrue()
        ->and($active->is('kaprog'))->toBeTrue()
        ->and($active->is('guru_pembimbing'))->toBeFalse()
        ->and($active->scope()['jurusan_id'])->toBe($kaprog->jurusan_id)
        ->and($active->scope()['kelas_id'])->toBeNull();
});
