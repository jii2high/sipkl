<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'master-data.kelola']);
        Permission::firstOrCreate(['name' => 'master-penilaian.kelola']);
        Permission::firstOrCreate(['name' => 'akun.kelola']);
        Role::firstOrCreate(['name' => 'siswa']);
        Role::firstOrCreate(['name' => 'kaprog']);
        Role::firstOrCreate(['name' => 'guru_pembimbing']);
        $hubin = Role::firstOrCreate(['name' => 'hubin']);
        $hubin->givePermissionTo(['master-data.kelola', 'master-penilaian.kelola', 'akun.kelola']);
    })
    ->in('Feature');

function hubinUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('hubin');

    return $user;
}

function asHubin(): TestCase
{
    return test()->actingAs(hubinUser());
}
