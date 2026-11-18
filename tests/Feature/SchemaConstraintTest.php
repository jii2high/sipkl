<?php

use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Invarian TRD.md §6.2 dijaga database
|--------------------------------------------------------------------------
| Setiap tes memasukkan baris yang melanggar; insert harus gagal. Bila tes
| lolos di MySQL, aturan benar-benar ditegakkan, bukan hanya oleh aplikasi.
*/

beforeEach(function () {
    $this->jurusan = Jurusan::factory()->create();
    $this->kelas = Kelas::factory()->create(['jurusan_id' => $this->jurusan->id]);
    $this->company = Company::factory()->create();
    $this->period = AcademicPeriod::factory()->create();
    $this->guru = User::factory()->create();
    $this->siswa = User::factory()->siswa($this->jurusan->id, $this->kelas->id)->create();
});

function studentApplicationRow(int $companyId, int $periodId, int $siswaId, array $overrides = []): array
{
    return $overrides + [
        'company_id' => $companyId,
        'academic_period_id' => $periodId,
        'siswa_id' => $siswaId,
        'status' => 'draft',
    ];
}

test('email pengguna wajib memakai domain sekolah', function () {
    expect(fn () => DB::table('users')->insert([
        'nis_nip' => '900001', 'name' => 'Luar', 'email' => 'luar@gmail.com',
        'password' => 'rahasia', 'user_type' => 'guru',
    ]))->toThrow(QueryException::class);
});

test('siswa wajib punya jurusan dan kelas', function () {
    expect(fn () => DB::table('users')->insert([
        'nis_nip' => '900002', 'name' => 'Siswa', 'email' => 'siswa900002@smkn1kotabekasi.sch.id',
        'password' => 'rahasia', 'user_type' => 'siswa',
    ]))->toThrow(QueryException::class);
});

test('tingkat kelas hanya 10, 11, atau 12', function () {
    expect(fn () => DB::table('kelas')->insert([
        'jurusan_id' => $this->jurusan->id, 'nama' => '13 RPL 1', 'tingkat' => 13,
    ]))->toThrow(QueryException::class);
});

test('peran kaprog wajib punya scope jurusan', function () {
    expect(fn () => DB::table('user_roles')->insert([
        'user_id' => $this->guru->id, 'role' => 'kaprog',
    ]))->toThrow(QueryException::class);
});

test('peran dan scope yang sama tidak boleh ganda', function () {
    $row = ['user_id' => $this->guru->id, 'role' => 'guru_pembimbing'];

    DB::table('user_roles')->insert($row);

    expect(fn () => DB::table('user_roles')->insert($row))->toThrow(QueryException::class);
});

test('provisional hanya sah untuk status sedang_pkl', function () {
    expect(fn () => DB::table('pengajuan_siswa')->insert(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id, ['is_provisional' => true],
    )))->toThrow(QueryException::class);
});

test('pengajuan_pkl_id wajib sejak surat pengantar terbit', function () {
    expect(fn () => DB::table('pengajuan_siswa')->insert(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id, ['status' => 'surat_pengantar_terbit'],
    )))->toThrow(QueryException::class);
});

test('hanya satu pengajuan aktif per siswa per periode', function () {
    $group = DB::table('pengajuan_pkl')->insertGetId([
        'company_id' => $this->company->id, 'academic_period_id' => $this->period->id, 'status' => 'terbentuk',
    ]);

    $aktif = [
        'pengajuan_pkl_id' => $group,
        'status' => 'sedang_pkl',
        'snapshot_company_json' => json_encode(['nama' => 'PT Contoh']),
        'tanggal_mulai_pkl' => '2026-01-05',
        'tanggal_selesai_pkl' => '2026-04-30',
    ];

    DB::table('pengajuan_siswa')->insert(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id, $aktif,
    ));

    expect(fn () => DB::table('pengajuan_siswa')->insert(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id, $aktif,
    )))->toThrow(QueryException::class);
});

test('status rejected membebaskan slot pengajuan aktif', function () {
    $group = DB::table('pengajuan_pkl')->insertGetId([
        'company_id' => $this->company->id, 'academic_period_id' => $this->period->id, 'status' => 'terbentuk',
    ]);

    $dasar = ['pengajuan_pkl_id' => $group, 'snapshot_company_json' => json_encode(['nama' => 'PT Contoh'])];

    DB::table('pengajuan_siswa')->insert(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id, $dasar + ['status' => 'rejected'],
    ));

    DB::table('pengajuan_siswa')->insert(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id, $dasar + ['status' => 'draft'],
    ));

    expect(DB::table('pengajuan_siswa')->count())->toBe(2);
});

test('FK komposit group menolak perusahaan yang tidak cocok', function () {
    $group = DB::table('pengajuan_pkl')->insertGetId([
        'company_id' => $this->company->id, 'academic_period_id' => $this->period->id, 'status' => 'terbentuk',
    ]);
    $perusahaanLain = Company::factory()->create();

    expect(fn () => DB::table('pengajuan_siswa')->insert(studentApplicationRow(
        $perusahaanLain->id, $this->period->id, $this->siswa->id, ['pengajuan_pkl_id' => $group],
    )))->toThrow(QueryException::class);
});

test('signer perusahaan terverifikasi wajib punya nama dan email', function () {
    expect(fn () => DB::table('companies')->insert([
        'nama' => 'PT Tanpa Signer', 'alamat' => 'Jalan', 'pembimbing_industri_email_verified_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('kode step TTD harus cocok dengan jenis penandatangan', function () {
    $pengajuanSiswa = DB::table('pengajuan_siswa')->insertGetId(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id,
    ));

    $versi = DB::table('laporan_versions')->insertGetId([
        'pengajuan_siswa_id' => $pengajuanSiswa, 'version_number' => 1,
        'file_path' => 'laporan.pdf', 'created_by' => $this->guru->id,
    ]);

    expect(fn () => DB::table('document_signatures')->insert([
        'pengajuan_siswa_id' => $pengajuanSiswa, 'laporan_version_id' => $versi,
        'step_order' => 3, 'signer_type' => 'kaprog', 'status' => 'pending',
    ]))->toThrow(QueryException::class);
});

test('step TTD unik per versi laporan', function () {
    $pengajuanSiswa = DB::table('pengajuan_siswa')->insertGetId(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id,
    ));

    $versi = DB::table('laporan_versions')->insertGetId([
        'pengajuan_siswa_id' => $pengajuanSiswa, 'version_number' => 1,
        'file_path' => 'laporan.pdf', 'created_by' => $this->guru->id,
    ]);

    $step = [
        'pengajuan_siswa_id' => $pengajuanSiswa, 'laporan_version_id' => $versi,
        'step_order' => 1, 'signer_type' => 'guru_pembimbing', 'status' => 'pending',
    ];

    DB::table('document_signatures')->insert($step);

    expect(fn () => DB::table('document_signatures')->insert($step))->toThrow(QueryException::class);
});

test('catatan penolakan TTD minimal 10 karakter', function () {
    $pengajuanSiswa = DB::table('pengajuan_siswa')->insertGetId(studentApplicationRow(
        $this->company->id, $this->period->id, $this->siswa->id,
    ));

    $versi = DB::table('laporan_versions')->insertGetId([
        'pengajuan_siswa_id' => $pengajuanSiswa, 'version_number' => 1,
        'file_path' => 'laporan.pdf', 'created_by' => $this->guru->id,
    ]);

    expect(fn () => DB::table('document_signatures')->insert([
        'pengajuan_siswa_id' => $pengajuanSiswa, 'laporan_version_id' => $versi,
        'step_order' => 1, 'signer_type' => 'guru_pembimbing',
        'status' => 'rejected', 'rejection_note' => 'singkat',
    ]))->toThrow(QueryException::class);
});

test('plotting unik per perusahaan dan periode', function () {
    $row = [
        'company_id' => $this->company->id, 'academic_period_id' => $this->period->id,
        'guru_id' => $this->guru->id, 'plotted_by' => $this->guru->id,
    ];

    DB::table('guru_pembimbing_plotting')->insert($row);

    expect(fn () => DB::table('guru_pembimbing_plotting')->insert($row))->toThrow(QueryException::class);
});

test('nomor surat unik per tahun ajaran', function () {
    $row = [
        'guru_id' => $this->guru->id, 'diajukan_oleh' => 'hubin',
        'tanggal_mulai' => '2026-01-05', 'tanggal_selesai' => '2026-01-06',
        'status' => 'disetujui', 'tahun_ajaran' => '2025/2026',
        'nomor_urut' => 1, 'nomor_surat' => '001/STV/2026',
    ];

    DB::table('surat_tugas_visitasi')->insert($row);

    expect(fn () => DB::table('surat_tugas_visitasi')->insert($row))->toThrow(QueryException::class);
});

test('satu magic link aktif per jenis dan target', function () {
    $row = [
        'token_hash' => str_repeat('a', 64), 'type' => 'industry_assessment',
        'reference_id' => 1, 'expires_at' => now()->addDay(),
    ];

    DB::table('magic_links')->insert($row);

    expect(fn () => DB::table('magic_links')->insert(
        ['token_hash' => str_repeat('b', 64)] + $row,
    ))->toThrow(QueryException::class);
});

test('ekspor selesai wajib punya berkas, checksum, dan tanggal', function () {
    expect(fn () => DB::table('accreditation_exports')->insert([
        'requested_by' => $this->guru->id, 'status' => 'completed',
    ]))->toThrow(QueryException::class);
});

test('submission penilaian industri unik per definisi, perusahaan, dan periode', function () {
    $definisi = DB::table('form_definitions')->insertGetId([
        'jurusan_id' => $this->jurusan->id, 'academic_period_id' => $this->period->id,
        'form_type' => 'penilaian_industri', 'nama' => 'Penilaian Industri', 'created_by' => $this->guru->id,
    ]);

    $row = [
        'form_definition_id' => $definisi, 'form_type' => 'penilaian_industri',
        'company_id' => $this->company->id, 'academic_period_id' => $this->period->id,
        'jawaban' => json_encode(['nilai_total' => 80]),
    ];

    DB::table('form_submissions')->insert($row);

    expect(fn () => DB::table('form_submissions')->insert($row))->toThrow(QueryException::class);
});

test('bentuk submission sidang wajib punya jadwal dan pengajuan', function () {
    $definisi = DB::table('form_definitions')->insertGetId([
        'jurusan_id' => $this->jurusan->id, 'academic_period_id' => $this->period->id,
        'form_type' => 'sidang', 'nama' => 'Nilai Sidang', 'created_by' => $this->guru->id,
    ]);

    expect(fn () => DB::table('form_submissions')->insert([
        'form_definition_id' => $definisi, 'form_type' => 'sidang',
        'jawaban' => json_encode(['nilai_total' => 80]),
    ]))->toThrow(QueryException::class);
});

test('nilai_total dihitung database dari JSON jawaban', function () {
    $definisi = DB::table('form_definitions')->insertGetId([
        'jurusan_id' => $this->jurusan->id, 'academic_period_id' => $this->period->id,
        'form_type' => 'penilaian_industri', 'nama' => 'Penilaian Industri', 'created_by' => $this->guru->id,
    ]);

    $id = DB::table('form_submissions')->insertGetId([
        'form_definition_id' => $definisi, 'form_type' => 'penilaian_industri',
        'company_id' => $this->company->id, 'academic_period_id' => $this->period->id,
        'jawaban' => json_encode(['nilai_total' => 87.5]),
    ]);

    expect((float) DB::table('form_submissions')->where('id', $id)->value('nilai_total'))->toBe(87.5);
});
