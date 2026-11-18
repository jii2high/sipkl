<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

/**
 * Akun demo satu per peran, ditambah satu akun penguji multi-peran
 * (peran disimpan di `user_roles`, TRD.md §6.1).
 * Kata sandi awal = NIS/NIP dengan `is_first_login = true`, sesuai BR-F1-2.
 */
class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $rpl = Jurusan::query()->where('kode', 'RPL')->firstOrFail();
        $kelasRpl10 = Kelas::query()->where('jurusan_id', $rpl->id)->where('nama', '10 RPL 1')->firstOrFail();

        $accounts = [
            [
                'user' => ['nis_nip' => '240001', 'name' => 'Ahmad Fauzi', 'user_type' => 'siswa', 'jurusan_id' => $rpl->id, 'kelas_id' => $kelasRpl10->id],
                'roles' => [['role' => 'siswa', 'jurusan_id' => null, 'kelas_id' => null]],
            ],
            [
                'user' => ['nis_nip' => '197001011997011001', 'name' => 'Siti Nurhaliza', 'user_type' => 'guru'],
                'roles' => [['role' => 'walas', 'jurusan_id' => null, 'kelas_id' => $kelasRpl10->id]],
            ],
            [
                'user' => ['nis_nip' => '197001011997011002', 'name' => 'Budi Santoso', 'user_type' => 'guru'],
                'roles' => [['role' => 'guru_pembimbing', 'jurusan_id' => null, 'kelas_id' => null]],
            ],
            [
                'user' => ['nis_nip' => '197001011997011003', 'name' => 'Dewi Lestari', 'user_type' => 'guru'],
                'roles' => [['role' => 'kaprog', 'jurusan_id' => $rpl->id, 'kelas_id' => null]],
            ],
            [
                'user' => ['nis_nip' => '197001011997011004', 'name' => 'Rudi Hartono', 'user_type' => 'hubin'],
                'roles' => [['role' => 'hubin', 'jurusan_id' => null, 'kelas_id' => null]],
            ],
            [
                'user' => ['nis_nip' => '197001011997011005', 'name' => 'Hendra Wijaya', 'user_type' => 'kepsek'],
                'roles' => [['role' => 'kepsek', 'jurusan_id' => null, 'kelas_id' => null]],
            ],
            // Akun penguji manual: alamat email nyata (bukan turunan NIS/NIP) dengan
            // tiga peran di panel `app` agar pemilih peran aktif ikut teruji.
            [
                'user' => [
                    'nis_nip' => '197001011997011006',
                    'name' => 'Fajri Muhammad',
                    'user_type' => 'guru',
                    'email' => 'fajri.muhammad@'.config('pkl.school.email_domain'),
                ],
                'roles' => [
                    ['role' => 'guru_pembimbing', 'jurusan_id' => null, 'kelas_id' => null],
                    ['role' => 'kaprog', 'jurusan_id' => $rpl->id, 'kelas_id' => null],
                    ['role' => 'walas', 'jurusan_id' => null, 'kelas_id' => $kelasRpl10->id],
                ],
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->firstOrCreate(
                ['nis_nip' => $account['user']['nis_nip']],
                $account['user'] + [
                    'email' => $account['user']['nis_nip'].'@'.config('pkl.school.email_domain'),
                    'password' => $account['user']['nis_nip'],
                    'is_first_login' => true,
                    'is_active' => true,
                ],
            );

            foreach ($account['roles'] as $role) {
                UserRole::query()->firstOrCreate(
                    ['user_id' => $user->id] + $role,
                );
            }
        }
    }
}
