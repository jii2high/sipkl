<?php

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function userPunyaRole(string $role): User
{
    $user = User::factory()->create();
    UserRole::create(['user_id' => $user->id, 'role' => $role]);

    return $user;
}

it('membuka panel hubin untuk user dengan active_role hubin', function () {
    $user = userPunyaRole('hubin');

    $this->actingAs($user)->withSession(['active_role' => 'hubin'])
        ->get('/hubin')->assertOk();
});

it('menolak panel hubin untuk user dengan active_role guru_pembimbing', function () {
    $user = userPunyaRole('guru_pembimbing');

    $this->actingAs($user)->withSession(['active_role' => 'guru_pembimbing'])
        ->get('/hubin')->assertForbidden();
});

it('membuka panel app untuk user dengan active_role guru_pembimbing', function () {
    $user = userPunyaRole('guru_pembimbing');

    $this->actingAs($user)->withSession(['active_role' => 'guru_pembimbing'])
        ->get('/app')->assertOk();
});

it('menolak panel app untuk user dengan active_role hubin', function () {
    $user = userPunyaRole('hubin');

    $this->actingAs($user)->withSession(['active_role' => 'hubin'])
        ->get('/app')->assertForbidden();
});

it('menolak kedua panel bila active_role tidak diisi', function () {
    $user = userPunyaRole('guru_pembimbing');

    $this->actingAs($user)->get('/hubin')->assertForbidden();
    $this->actingAs($user)->get('/app')->assertForbidden();
});

it('menolak panel bila active_role tidak dimiliki user', function () {
    $user = userPunyaRole('guru_pembimbing');

    $this->actingAs($user)->withSession(['active_role' => 'kaprog'])
        ->get('/app')->assertForbidden();
});
