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
            'master-perusahaan.kelola',
            'master-periode-pkl.kelola',
            'master-kompetensi-keahlian.kelola',
            'master-indikator-penilaian.kelola',
            'master-section-penilaian.kelola',
            'master-akun.kelola',
        ];

        collect($permissions)->each(fn ($permission) => Permission::firstOrCreate(['name' => $permission]));

        $hubin = Role::where('name', 'hubin')->first();
        if ($hubin) {
            $hubin->syncPermissions($permissions);
        }
    }
}
