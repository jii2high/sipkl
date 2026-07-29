<?php

namespace Database\Seeders;

use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            KompetensiKeahlianSeeder::class,
        ]);

        $rpl = KompetensiKeahlian::where('acronym', 'RPL')->first();

        $siswa = User::factory()->create([
            'name' => 'Siswa RPL',
            'email' => 'siswa@test.com',
            'password' => Hash::make('1234567890'),
        ]);
        $siswa->assignRole('siswa');
        SiswaProfile::create([
            'user_id' => $siswa->id,
            'nis' => '1234567890',
            'kelas' => 'XII RPL 1',
            'kompetensi_keahlian_id' => $rpl->id,
        ]);

        $kaprog = User::factory()->create([
            'name' => 'Kaprog RPL',
            'email' => 'kaprog@test.com',
            'password' => Hash::make('198001012010011001'),
        ]);
        GuruProfile::create([
            'user_id' => $kaprog->id,
            'nip' => '198001012010011001',
            'jabatan' => 'kaprog',
        ]);

        $guru = User::factory()->create([
            'name' => 'Guru Pembimbing',
            'email' => 'guru@test.com',
            'password' => Hash::make('198001012010011002'),
        ]);
        GuruProfile::create([
            'user_id' => $guru->id,
            'nip' => '198001012010011002',
            'jabatan' => 'guru_pembimbing',
        ]);

        $hubin = User::factory()->create([
            'name' => 'Staf Hubin',
            'email' => 'hubin@test.com',
            'password' => Hash::make('198001012010011003'),
        ]);
        GuruProfile::create([
            'user_id' => $hubin->id,
            'nip' => '198001012010011003',
            'jabatan' => 'hubin',
        ]);
    }
}
