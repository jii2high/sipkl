<?php

namespace Database\Seeders;

use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use App\Models\PengajuanPkl;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
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
            KompetensiKeahlianSeeder::class,
            PermissionSeeder::class,
        ]);

        $rpl = KompetensiKeahlian::where('acronym', 'RPL')->first();
        $tkj = KompetensiKeahlian::where('acronym', 'TKJ')->first();
        $mm = KompetensiKeahlian::where('acronym', 'MM')->first();
        $akl = KompetensiKeahlian::where('acronym', 'AKL')->first();

        $pw = Hash::make('123456');

        // ─── SISWA ────────────────────────────────────────────

        $fajri = User::create(['name' => 'Muhammad Fajri', 'email' => 'fajri.muhammad@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        $fajri->assignRole('siswa');
        SiswaProfile::create(['user_id' => $fajri->id, 'nis' => '0011223301', 'kelas' => 'XII RPL 1', 'kompetensi_keahlian_id' => $rpl->id]);

        $fikri = User::create(['name' => 'Muhammad Fikri', 'email' => 'fikri.muhammad@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        $fikri->assignRole('siswa');
        SiswaProfile::create(['user_id' => $fikri->id, 'nis' => '0011223302', 'kelas' => 'XII RPL 1', 'kompetensi_keahlian_id' => $rpl->id]);

        $bagas = User::create(['name' => 'Bagas Setiawan', 'email' => 'setiawan.bagas@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        $bagas->assignRole('siswa');
        SiswaProfile::create(['user_id' => $bagas->id, 'nis' => '0011223303', 'kelas' => 'XII TKJ 1', 'kompetensi_keahlian_id' => $tkj->id]);

        $pedrosa = User::create(['name' => 'Pedrosa Simanjuntak', 'email' => 'simanjuntak.pedrosa@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        $pedrosa->assignRole('siswa');
        SiswaProfile::create(['user_id' => $pedrosa->id, 'nis' => '0011223304', 'kelas' => 'XII MM 1', 'kompetensi_keahlian_id' => $mm->id]);

        $nawfal = User::create(['name' => 'Muhammad Nawfal Hakim', 'email' => 'hakim.nawfal@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        $nawfal->assignRole('siswa');
        SiswaProfile::create(['user_id' => $nawfal->id, 'nis' => '0011223305', 'kelas' => 'XII AKL 1', 'kompetensi_keahlian_id' => $akl->id]);

        // ─── GURU ─────────────────────────────────────────────
        // Observer syncs role otomatis dari jabatan

        $della = User::create(['name' => 'Della Chaerani', 'email' => 'chaerani.della@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        GuruProfile::create(['user_id' => $della->id, 'nip' => '198501012010012001', 'jabatan' => 'kaprog', 'kompetensi_keahlian_id' => $rpl->id]);

        $nikmah = User::create(['name' => 'Nikmah Handayani', 'email' => 'handayani.nikmah@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        GuruProfile::create(['user_id' => $nikmah->id, 'nip' => '198601012010012002', 'jabatan' => 'kaprog', 'kompetensi_keahlian_id' => $tkj->id]);

        $satria = User::create(['name' => 'Satria Adiputra', 'email' => 'adiputra.satria@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        GuruProfile::create(['user_id' => $satria->id, 'nip' => '198701012010012003', 'jabatan' => 'guru_pembimbing', 'kompetensi_keahlian_id' => $rpl->id]);

        $ilham = User::create(['name' => 'Muhammad Ilham Kamil', 'email' => 'kamil.muhammad@smkn1kotabekasi.sch.id', 'password' => $pw, 'email_verified_at' => now()]);
        GuruProfile::create(['user_id' => $ilham->id, 'nip' => '198801012010012004', 'jabatan' => 'hubin']);

        // ─── PERUSAHAAN ───────────────────────────────────────

        $ptTm = Perusahaan::create(['nama' => 'PT Teknologi Maju', 'alamat' => 'Jl. Sudirman No. 123, Bekasi', 'telp' => '021-8888001', 'email' => 'info@teknologimaju.co.id', 'kuota_saran' => 5]);
        $cvKd = Perusahaan::create(['nama' => 'CV Karya Digital', 'alamat' => 'Jl. Ahmad Yani No. 45, Bekasi', 'telp' => '021-8888002', 'email' => 'halo@karyadigital.co.id', 'kuota_saran' => 3]);
        $ptSmk = Perusahaan::create(['nama' => 'PT Solusi Media Kreatif', 'alamat' => 'Jl. Raya Narogong Km 10, Bekasi', 'telp' => '021-8888003', 'email' => 'contact@mediakreatif.co.id', 'kuota_saran' => 4]);
        $ptNs = Perusahaan::create(['nama' => 'PT Network Solution', 'alamat' => 'Jl. Chairil Anwar No. 78, Bekasi', 'telp' => '021-8888004', 'email' => 'support@networksolution.co.id', 'kuota_saran' => 6]);
        $bca = Perusahaan::create(['nama' => 'Bank BCA', 'alamat' => 'Jl. KH. Noer Ali No. 1, Bekasi', 'telp' => '021-8888005', 'email' => 'cs@bca.co.id', 'kuota_saran' => 10]);

        // ─── PERIODE PKL ──────────────────────────────────────

        $periodeLama = PeriodePkl::create(['nama' => 'Ganjil 2025/2026', 'tanggal_mulai' => '2025-07-14', 'tanggal_selesai' => '2025-12-20', 'status_aktif' => false]);
        $periodeAktif = PeriodePkl::create(['nama' => 'Ganjil 2026/2027', 'tanggal_mulai' => '2026-07-13', 'tanggal_selesai' => '2026-12-19', 'status_aktif' => true]);

        // ─── PENGAJUAN PKL ────────────────────────────────────

        // 1. Fikri — Draft (belum submit, periode aktif)
        PengajuanPkl::create([
            'siswa_id' => $fikri->siswaProfile->id,
            'perusahaan_id' => $cvKd->id,
            'periode_id' => $periodeAktif->id,
            'status' => 'draft',
        ]);

        // 2. Fajri — Disetujui Kaprog RPL (periode aktif, siap terbit surat pengantar)
        PengajuanPkl::create([
            'siswa_id' => $fajri->siswaProfile->id,
            'perusahaan_id' => $ptTm->id,
            'periode_id' => $periodeAktif->id,
            'status' => 'disetujui',
            'kaprog_id' => (int) $della->id,
            'verified_kaprog_at' => now(),
        ]);

        // 3. Bagas — Diajukan ke Kaprog TKJ (periode aktif)
        PengajuanPkl::create([
            'siswa_id' => $bagas->siswaProfile->id,
            'perusahaan_id' => $ptNs->id,
            'periode_id' => $periodeAktif->id,
            'status' => 'diajukan',
        ]);

        // 4. Pedrosa — Ditolak Kaprog MM (periode aktif)
        $p3 = PengajuanPkl::create([
            'siswa_id' => $pedrosa->siswaProfile->id,
            'perusahaan_id' => $ptSmk->id,
            'periode_id' => $periodeAktif->id,
            'status' => 'ditolak',
            'kaprog_id' => (int) $della->id,
            'catatan_kaprog' => 'Lengkapi profil perusahaan tujuan dengan alamat yang valid.',
        ]);

        // 5. Fajri — Disetujui (periode lampau, buat uji Riwayat PKL)
        PengajuanPkl::create([
            'siswa_id' => $fajri->siswaProfile->id,
            'perusahaan_id' => $ptTm->id,
            'periode_id' => $periodeLama->id,
            'status' => 'disetujui',
            'kaprog_id' => (int) $della->id,
            'verified_kaprog_at' => now(),
        ]);
    }
}
