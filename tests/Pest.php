<?php

use App\Models\GuruProfile;
use App\Models\KompetensiKeahlian;
use App\Models\PengajuanPkl;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

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

        Role::firstOrCreate(['name' => 'siswa'])->givePermissionTo([
            'pengajuan.ajukan', 'pengajuan.lihat-sendiri', 'riwayat.lihat-sendiri', 'surat-balasan.upload',
            'laporan-akhir.upload',
        ]);
        Role::firstOrCreate(['name' => 'kaprog'])->givePermissionTo([
            'pengajuan.lihat', 'pengajuan.setujui', 'pengajuan.tolak', 'penugasan.kelola',
            'master-penilaian.kelola', 'surat-tugas.kelola',
        ]);
        Role::firstOrCreate(['name' => 'guru_pembimbing'])->givePermissionTo([
            'kunjungan.isi', 'kunjungan.lihat', 'penilaian.lihat', 'penilaian.isi',
        ]);
        Role::firstOrCreate(['name' => 'hubin'])->givePermissionTo([
            'master-data.kelola', 'master-penilaian.kelola', 'akun.kelola',
            'surat.terbitkan', 'surat-balasan.verifikasi', 'persuratan.bundling', 'surat-tugas.kelola',
        ]);
    })
    ->in('Feature');

function hubinUser(): User
{
    return tap(User::factory()->create(), fn ($u) => $u->assignRole('hubin'));
}

function siswaUser(): User
{
    $kk = KompetensiKeahlian::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('siswa');
    SiswaProfile::factory()->create([
        'user_id' => $user->id,
        'kompetensi_keahlian_id' => $kk->id,
    ]);

    return $user->unsetRelation('siswaProfile')->load('siswaProfile');
}

function kaprogUser(): User
{
    $kk = KompetensiKeahlian::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('kaprog');
    GuruProfile::factory()->create([
        'user_id' => $user->id,
        'jabatan' => 'kaprog',
        'kompetensi_keahlian_id' => $kk->id,
    ]);

    return $user;
}

function guruPembimbingUser(): User
{
    $kk = KompetensiKeahlian::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('guru_pembimbing');
    GuruProfile::factory()->create([
        'user_id' => $user->id,
        'jabatan' => 'guru_pembimbing',
        'kompetensi_keahlian_id' => $kk->id,
    ]);

    return $user->unsetRelation('guruProfile')->load('guruProfile');
}

function asHubin(): TestCase
{
    return test()->actingAs(hubinUser());
}

function asKaprog(): TestCase
{
    return test()->actingAs(kaprogUser());
}

function asGuruPembimbing(): TestCase
{
    return test()->actingAs(guruPembimbingUser());
}

function asSiswa(): TestCase
{
    return test()->actingAs(siswaUser());
}

/**
 * Setup minimal data for M4 surat test: perusahaan, periode aktif, pengajuan disetujui.
 */
function siapkanSuratPengantar(): array
{
    $periode = PeriodePkl::factory()->create(['status_aktif' => true]);
    $perusahaan = Perusahaan::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('siswa');
    $kk = KompetensiKeahlian::factory()->create();
    $profile = SiswaProfile::factory()->create([
        'user_id' => $user->id,
        'kompetensi_keahlian_id' => $kk->id,
    ]);
    $user->load('siswaProfile');
    $pengajuan = PengajuanPkl::factory()->create([
        'siswa_id' => $profile->id,
        'perusahaan_id' => $perusahaan->id,
        'periode_id' => $periode->id,
        'status' => 'disetujui',
    ]);

    return compact('periode', 'perusahaan', 'user', 'pengajuan', 'kk');
}
