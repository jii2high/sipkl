<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        collect(['siswa', 'kaprog', 'guru_pembimbing', 'hubin'])
            ->each(fn ($role) => Role::firstOrCreate(['name' => $role]));

        $permissions = [
            'master-data.kelola',
            'master-penilaian.kelola',
            'akun.kelola',
        ];

        collect($permissions)
            ->each(fn ($perm) => Permission::firstOrCreate(['name' => $perm]));

        Role::findByName('hubin')->givePermissionTo($permissions);
    }
}
