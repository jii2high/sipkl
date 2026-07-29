<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Storage::fake('local');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permissions = [
        'master-akun.kelola',
        'master-perusahaan.kelola',
        'master-periode-pkl.kelola',
        'master-kompetensi-keahlian.kelola',
        'master-indikator-penilaian.kelola',
        'master-section-penilaian.kelola',
    ];

    collect($permissions)->each(fn ($permission) => Permission::firstOrCreate(['name' => $permission]));

    Role::firstOrCreate(['name' => 'guru_pembimbing']);
    Role::firstOrCreate(['name' => 'hubin'])->syncPermissions($permissions);
});

test('hubin dapat mengakses halaman import export', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $user->assignRole('hubin');

    $response = $this->actingAs($user)->get('/hubin/import-export');

    $response->assertOk();
});

test('non hubin tidak dapat mengakses halaman import export', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $user->assignRole('guru_pembimbing');

    $response = $this->actingAs($user)->get('/hubin/import-export');

    $response->assertStatus(403);
});

test('hubin dapat mengunduh akun export', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $user->assignRole('hubin');

    $response = $this->actingAs($user)->get('/hubin/import-export/akun');

    $response->assertOk();
    $response->assertHeader('content-disposition');
});
