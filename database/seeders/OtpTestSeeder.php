<?php

namespace Database\Seeders;

use App\Models\KompetensiKeahlian;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OtpTestSeeder extends Seeder
{
    public function run(): void
    {

        $rpl = KompetensiKeahlian::where('acronym', 'RPL')->first();

        $otpTestUser = User::updateOrCreate(['name' => 'Muhammad Fajri', 'email' => 'mfajri110301@gmail.com', 'password' => Hash::make('password123'), 'email_verified_at' => now(), 'must_change_password' => true]);
        $otpTestUser->assignRole('siswa');
        SiswaProfile::updateOrCreate(['user_id' => $otpTestUser->id, 'nis' => '0000000001', 'kelas' => 'XII RPL 1', 'kompetensi_keahlian_id' => $rpl->id]);
    }
}
