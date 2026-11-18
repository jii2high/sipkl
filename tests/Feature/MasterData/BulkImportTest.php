<?php

use App\Exports\TemplateExport;
use App\Filament\Hubin\Pages\BulkImport;
use App\Imports\CompaniesImport;
use App\Imports\ImportReport;
use App\Imports\PeriodsImport;
use App\Imports\UsersImport;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

const BULK_HEADING_PERUSAHAAN = [
    'Nama Perusahaan',
    'Alamat',
    'Email',
    'Website',
    'Telepon/Fax',
    'Nama Kontak',
    'Telepon Kontak',
    'Nama Pembimbing Industri',
    'Email Pembimbing Industri',
    'Nama Kepala Industri',
    'Email Kepala Industri',
];

const BULK_HEADING_AKUN = [
    'NIS/NIP',
    'Nama Lengkap',
    'Email Sekolah',
    'Tipe Pengguna',
    'Jurusan',
    'Kelas',
    'Aktif',
];

const BULK_HEADING_PERIODE = [
    'Tahun Ajaran',
    'Batch',
    'Tanggal Mulai',
    'Tanggal Selesai',
    'Aktif',
    'Kunci Pengajuan Baru',
];

function bulkBerkasUji(string $nama, array $headings, array $rows): string
{
    Excel::store(new TemplateExport($headings, $rows, 'Data'), $nama, 'local');

    return Storage::disk('local')->path($nama);
}

function bulkLoginSebagai(string $role): User
{
    $user = User::factory()->create();
    UserRole::create(['user_id' => $user->id, 'role' => $role]);

    test()->actingAs($user)->withSession(['active_role' => $role]);

    return $user;
}

beforeEach(function () {
    Filament::setCurrentPanel('hubin');
});

it('mengimpor 17 dari 20 baris perusahaan dan melaporkan 3 email duplikat (AC-12)', function () {
    $rows = [];

    for ($i = 1; $i <= 20; $i++) {
        $rows[] = [
            'PT Uji '.$i,
            'Jl. Uji No. '.$i.', Bekasi',
            'pt'.$i.'@contoh.co.id',
            'pt'.$i.'.contoh.co.id',
            '0215550'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'Kontak '.$i,
            '0812000000'.$i,
            '',
            '',
            '',
            '',
        ];
    }

    // Tiga baris memakai email yang sudah dipakai baris sebelumnya.
    $rows[3][2] = 'pt1@contoh.co.id';
    $rows[8][2] = 'pt2@contoh.co.id';
    $rows[14][2] = 'pt3@contoh.co.id';

    $berkas = bulkBerkasUji('perusahaan-uji.xlsx', BULK_HEADING_PERUSAHAAN, $rows);
    $report = new ImportReport;

    Excel::import(new CompaniesImport($report), $berkas);

    expect(Company::count())->toBe(17)
        ->and($report->total())->toBe(20)
        ->and($report->jumlahBerhasil())->toBe(17)
        ->and($report->jumlahGagal())->toBe(3)
        ->and(collect($report->daftarGagal())->pluck('baris')->all())->toBe([5, 10, 16])
        ->and($report->daftarGagal()[0]['pesan'])->toContain('Email perusahaan ini sudah terdaftar');

    Storage::disk('local')->delete('perusahaan-uji.xlsx');
});

it('menandai perusahaan mirip sebagai perlu tinjau tanpa memblokir (AC-11)', function () {
    $berkas = bulkBerkasUji('mirip-uji.xlsx', BULK_HEADING_PERUSAHAAN, [
        ['CV Ajikyo', 'Jl. Merdeka No. 1, Bekasi', 'ajikyo@contoh.co.id', 'ajikyo.co.id', '0215550001', '', '', '', '', '', ''],
        ['CV Ajikiyo', 'Jl. Merdeka No. 2, Bekasi', 'ajikiyo@contoh.co.id', 'ajikiyo.co.id', '0215550002', '', '', '', '', '', ''],
    ]);

    $report = new ImportReport;

    Excel::import(new CompaniesImport($report), $berkas);

    expect(Company::count())->toBe(2)
        ->and($report->jumlahBerhasil())->toBe(2)
        ->and($report->jumlahGagal())->toBe(0)
        ->and($report->jumlahPerluTinjau())->toBe(1)
        ->and($report->daftarPerluTinjau()[0]['baris'])->toBe(3)
        ->and($report->daftarPerluTinjau()[0]['saran'])->toContain('CV Ajikyo');

    Storage::disk('local')->delete('mirip-uji.xlsx');
});

it('menolak baris perusahaan tanpa nama atau alamat, baris lain tetap masuk', function () {
    $berkas = bulkBerkasUji('perusahaan-tidak-lengkap.xlsx', BULK_HEADING_PERUSAHAAN, [
        ['PT Lengkap', 'Jl. Lengkap No. 1', 'lengkap@contoh.co.id', '', '', '', '', '', '', '', ''],
        ['', 'Jl. Tanpa Nama No. 2', 'tanpanama@contoh.co.id', '', '', '', '', '', '', '', ''],
        ['PT Tanpa Alamat', '', 'tanpaalamat@contoh.co.id', '', '', '', '', '', '', '', ''],
    ]);

    $report = new ImportReport;

    Excel::import(new CompaniesImport($report), $berkas);

    expect(Company::count())->toBe(1)
        ->and($report->jumlahBerhasil())->toBe(1)
        ->and($report->jumlahGagal())->toBe(2)
        ->and($report->daftarGagal()[0]['pesan'])->toContain('Nama perusahaan wajib diisi')
        ->and($report->daftarGagal()[1]['pesan'])->toContain('Alamat perusahaan wajib diisi');

    Storage::disk('local')->delete('perusahaan-tidak-lengkap.xlsx');
});

it('mengimpor periode akademik, menerima tangal Excel, dan menolak duplikat', function () {
    $serial = ExcelDate::PHPToExcel(new DateTimeImmutable('2027-01-05'));

    $berkas = bulkBerkasUji('periode-uji.xlsx', BULK_HEADING_PERIODE, [
        ['2026/2027', '1', '2026-07-01', '2026-12-31', 'ya', 'tidak'],
        ['2026-2027', '1', '2026-07-01', '2026-12-31', 'ya', 'tidak'],
        ['2026/2027', '2', $serial, '2027-06-30', 'ya', 'ya'],
        ['2026/2027', '3', '2026-07-01', '2026-12-31', 'ya', 'tidak'],
        ['2027/2028', '1', '2027-07-01', '2027-06-30', 'ya', 'tidak'],
    ]);

    $report = new ImportReport;

    Excel::import(new PeriodsImport($report), $berkas);

    expect(AcademicPeriod::count())->toBe(2)
        ->and($report->jumlahBerhasil())->toBe(2)
        ->and($report->jumlahGagal())->toBe(3);

    $batchDua = AcademicPeriod::query()->where('batch', '2')->sole();

    expect($batchDua->tahun_ajaran)->toBe('2026/2027')
        ->and($batchDua->tanggal_mulai->format('Y-m-d'))->toBe('2027-01-05')
        ->and($batchDua->locked_for_new_submission)->toBeTrue();

    expect(collect($report->daftarGagal())->pluck('pesan')->implode(' '))
        ->toContain('sudah terdaftar')
        ->toContain('Batch harus 1 atau 2')
        ->toContain('Tanggal selesai tidak boleh sebelum tanggal mulai');

    Storage::disk('local')->delete('periode-uji.xlsx');
});

it('mengimpor akun dengan jurusan/kelas berdasarkan nama dan password awal NIS/NIP', function () {
    $jurusan = Jurusan::factory()->create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak']);
    Kelas::factory()->create(['jurusan_id' => $jurusan->id, 'nama' => 'XI RPL 1', 'tingkat' => 11]);

    $berkas = bulkBerkasUji('akun-uji.xlsx', BULK_HEADING_AKUN, [
        ['123456', 'Siswa Baru', 'siswa.baru@smkn1kotabekasi.sch.id', 'siswa', 'RPL', 'XI RPL 1', 'ya'],
        ['123457', 'Siswa Duplikat', 'siswa.baru@smkn1kotabekasi.sch.id', 'siswa', 'RPL', 'XI RPL 1', 'ya'],
        ['123458', 'Siswa Salah Jurusan', 'siswa.salah@smkn1kotabekasi.sch.id', 'siswa', 'TKJ', 'XI TKJ 1', 'ya'],
        ['654321', 'Guru Baru', 'guru.baru@smkn1kotabekasi.sch.id', 'guru', '', '', 'tidak'],
    ]);

    $report = new ImportReport;

    Excel::import(new UsersImport($report), $berkas);

    expect(User::count())->toBe(2)
        ->and($report->jumlahBerhasil())->toBe(2)
        ->and($report->jumlahGagal())->toBe(2);

    $siswa = User::query()->where('nis_nip', '123456')->sole();
    $guru = User::query()->where('nis_nip', '654321')->sole();

    expect($siswa->jurusan_id)->toBe($jurusan->id)
        ->and($siswa->kelas_id)->not->toBeNull()
        ->and($siswa->is_first_login)->toBeTrue()
        ->and(Hash::check('123456', $siswa->password))->toBeTrue()
        ->and($guru->is_active)->toBeFalse();

    Storage::disk('local')->delete('akun-uji.xlsx');
});

it('menampilkan halaman impor massal hanya untuk hubin', function () {
    bulkLoginSebagai('hubin');

    $this->get('/hubin/bulk-import')->assertOk()->assertSee('Impor Massal');

    bulkLoginSebagai('guru_pembimbing');

    $this->get('/hubin/bulk-import')->assertForbidden();
});

it('mengunggah berkas lewat halaman impor dan menampilkan hasil', function () {
    bulkLoginSebagai('hubin');

    $berkas = bulkBerkasUji('halaman-uji.xlsx', BULK_HEADING_PERUSAHAAN, [
        ['PT Halaman Satu', 'Jl. Halaman No. 1', 'satu@contoh.co.id', '', '', '', '', '', '', '', ''],
        ['PT Halaman Dua', 'Jl. Halaman No. 2', 'dua@contoh.co.id', '', '', '', '', '', '', '', ''],
        ['PT Halaman Satu Lagi', 'Jl. Halaman No. 3', 'satu@contoh.co.id', '', '', '', '', '', '', '', ''],
    ]);

    Storage::fake('tmp-for-tests');

    $upload = UploadedFile::fake()->createWithContent('halaman-uji.xlsx', file_get_contents($berkas));

    livewire(BulkImport::class)
        ->fillForm([
            'jenis' => 'perusahaan',
            'file' => $upload,
        ])
        ->call('import')
        ->assertHasNoFormErrors()
        ->assertSet('hasil.total', 3)
        ->assertSet('hasil.ringkasan', '2 berhasil, 1 gagal, 1 perlu tinjau dari 3 baris.');

    expect(Company::count())->toBe(2);

    Storage::disk('local')->delete('halaman-uji.xlsx');
});

it('mengunduh template xlsx sesuai jenis data', function () {
    bulkLoginSebagai('hubin');

    livewire(BulkImport::class)
        ->fillForm(['jenis' => 'akun'])
        ->callAction('unduhTemplate')
        ->assertFileDownloaded('template-impor-akun.xlsx');
});

it('menolak unggahan tanpa berkas', function () {
    bulkLoginSebagai('hubin');

    livewire(BulkImport::class)
        ->fillForm(['jenis' => 'perusahaan', 'file' => null])
        ->call('import')
        ->assertHasFormErrors(['file']);

    expect(Company::count())->toBe(0);
});
