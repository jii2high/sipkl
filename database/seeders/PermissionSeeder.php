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
            'pengajuan.ajukan',
            'pengajuan.lihat-sendiri',
            'pengajuan.lihat',
            'pengajuan.setujui',
            'pengajuan.tolak',
            'riwayat.lihat-sendiri',
        ];

        collect($permissions)->each(fn ($p) => Permission::firstOrCreate(['name' => $p]));

        Role::findByName('hubin')->givePermissionTo([
            'master-data.kelola',
            'master-penilaian.kelola',
            'akun.kelola',
        ]);

        Role::findByName('siswa')->givePermissionTo([
            'pengajuan.ajukan',
            'pengajuan.lihat-sendiri',
            'riwayat.lihat-sendiri',
        ]);

        Role::findByName('kaprog')->givePermissionTo([
            'pengajuan.lihat',
            'pengajuan.setujui',
            'pengajuan.tolak',
        ]);
    }
}
