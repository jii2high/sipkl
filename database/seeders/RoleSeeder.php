<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        collect(['siswa', 'kaprog', 'guru_pembimbing', 'hubin'])
            ->each(fn ($role) => Role::create(['name' => $role]));
    }
}
