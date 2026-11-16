<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

/**
 * Data dummy untuk uji manual (TASKS M0.11). Jangan memakai data siswa nyata.
 *
 * Password awal setiap akun = NIS/NIP (cast `hashed` di model User).
 * Email = {nis_nip}@smkn1kotabekasi.sch.id (huruf kecil).
 */
class DevSeeder extends Seeder
{
    public function run(): void
    {
        $rpl = Jurusan::create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak']);
        $tkr = Jurusan::create(['kode' => 'TKR', 'nama' => 'Teknik Kendaraan Ringan']);

        $xiRpl1 = Kelas::create(['jurusan_id' => $rpl->id, 'nama' => 'XI RPL 1', 'tingkat' => 11]);
        $xiRpl2 = Kelas::create(['jurusan_id' => $rpl->id, 'nama' => 'XI RPL 2', 'tingkat' => 11]);
        $xiTkr1 = Kelas::create(['jurusan_id' => $tkr->id, 'nama' => 'XI TKR 1', 'tingkat' => 11]);
        $xiiTkr1 = Kelas::create(['jurusan_id' => $tkr->id, 'nama' => 'XII TKR 1', 'tingkat' => 12]);

        AcademicPeriod::create([
            'tahun_ajaran' => '2026/2027',
            'batch' => '1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'is_active' => true,
            'locked_for_new_submission' => false,
        ]);

        $hubin = $this->akun('HUBIN001', 'Siti Hubin', 'hubin');
        $this->akun('KEPSEK001', 'Dedi Kepala Sekolah', 'kepsek');

        $kaprog = $this->akun('KAPRPL01', 'Ani Kaprog RPL', 'kaprog', jurusan: $rpl);
        $walas = $this->akun('WALRPL11', 'Bayu Walas XI RPL 1', 'walas', kelas: $xiRpl1);
        $guru = $this->akun('GURU001', 'Citra Guru Pembimbing', 'guru_pembimbing');

        // Guru multi-role: guru pembimbing sekaligus walas kelas lain.
        UserRole::create([
            'user_id' => $guru->id,
            'role' => 'walas',
            'kelas_id' => $xiRpl2->id,
        ]);

        $siswaSeeds = [
            ['SISWA001', 'Siswa RPL Satu', $xiRpl1],
            ['SISWA002', 'Siswa RPL Dua', $xiRpl1],
            ['SISWA003', 'Siswa RPL Tiga', $xiRpl1],
            ['SISWA004', 'Siswa RPL Empat', $xiRpl2],
            ['SISWA005', 'Siswa TKR Satu', $xiTkr1],
            ['SISWA006', 'Siswa TKR Dua', $xiiTkr1],
        ];

        foreach ($siswaSeeds as [$nis, $nama, $kelas]) {
            $this->akun($nis, $nama, 'siswa', kelas: $kelas);
        }

        $this->command?->info('Data dev dibuat. Password awal = NIS/NIP. Contoh: '.$hubin->email.' / HUBIN001, '.$kaprog->email.' / KAPRPL01, '.$walas->email.' / WALRPL11.');
    }

    private function akun(
        string $nisNip,
        string $nama,
        string $role,
        ?Jurusan $jurusan = null,
        ?Kelas $kelas = null,
    ): User {
        $user = User::create([
            'nis_nip' => $nisNip,
            'name' => $nama,
            'email' => strtolower($nisNip).'@smkn1kotabekasi.sch.id',
            'password' => $nisNip,
            'user_type' => $this->userTypeFor($role),
            'jurusan_id' => $jurusan?->id ?? $kelas?->jurusan_id,
            'kelas_id' => $kelas?->id,
            'is_first_login' => true,
            'is_active' => true,
        ]);

        UserRole::create([
            'user_id' => $user->id,
            'role' => $role,
            'jurusan_id' => $role === 'kaprog' ? $jurusan?->id : null,
            'kelas_id' => $role === 'walas' ? $kelas?->id : null,
        ]);

        return $user;
    }

    private function userTypeFor(string $role): string
    {
        return match ($role) {
            'siswa' => 'siswa',
            'hubin' => 'hubin',
            'kepsek' => 'kepsek',
            default => 'guru',
        };
    }
}
