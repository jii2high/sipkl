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
            'surat.terbitkan',
            'surat-balasan.upload',
            'surat-balasan.verifikasi',
            'persuratan.bundling',
            'penugasan.kelola',
            'surat-tugas.kelola',
            'kunjungan.isi',
            'kunjungan.lihat',
            'laporan-akhir.upload',
            'penilaian.lihat',
            'penilaian.isi',
        ];

        collect($permissions)->each(fn ($p) => Permission::firstOrCreate(['name' => $p]));

        Role::findByName('hubin')->givePermissionTo([
            'master-data.kelola',
            'master-penilaian.kelola',
            'akun.kelola',
            'surat.terbitkan',
            'surat-balasan.verifikasi',
            'persuratan.bundling',
        ]);

        Role::findByName('siswa')->givePermissionTo([
            'pengajuan.ajukan',
            'pengajuan.lihat-sendiri',
            'riwayat.lihat-sendiri',
            'surat-balasan.upload',
            'laporan-akhir.upload',
        ]);

        Role::findByName('kaprog')->givePermissionTo([
            'pengajuan.lihat',
            'pengajuan.setujui',
            'pengajuan.tolak',
            'penugasan.kelola',
        ]);

        Role::findByName('hubin')->givePermissionTo([
            'master-data.kelola',
            'master-penilaian.kelola',
            'akun.kelola',
            'surat.terbitkan',
            'surat-balasan.verifikasi',
            'persuratan.bundling',
            'surat-tugas.kelola',
        ]);

        Role::findByName('guru_pembimbing')->givePermissionTo([
            'kunjungan.isi',
            'kunjungan.lihat',
            'penilaian.lihat',
            'penilaian.isi',
        ]);
    }
}
