<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'master-data.kelola',
            'master-penilaian.kelola',
            'akun.kelola',
        ];

        collect($permissions)->each(fn ($p) => Permission::firstOrCreate(['name' => $p]));

        Role::findByName('hubin')->givePermissionTo($permissions);
    }
}
