<?php

use App\Filament\Hubin\Resources\Companies\Pages\ManageCompanies;
use App\Filament\Hubin\Resources\Users\Pages\ManageUsers;
use App\Models\Company;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Support\ImportSheet;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));
    Storage::fake('local');

    $this->actingAs(pklUser(['hubin']))->withSession(['active_role' => 'hubin']);
});

test('ImportSheet membaca judul kolom dan melewati baris kosong', function () {
    $file = UploadedFile::fake()->createWithContent(
        'perusahaan.csv',
        "nama,alamat,email\nPT A,Jl A,a@pt.test\n\nPT B,Jl B,\n",
    );

    expect(ImportSheet::rows($file))->toBe([
        ['nama' => 'PT A', 'alamat' => 'Jl A', 'email' => 'a@pt.test'],
        ['nama' => 'PT B', 'alamat' => 'Jl B', 'email' => null],
    ]);
});

test('action impor perusahaan memasukkan baris dari berkas', function () {
    $file = UploadedFile::fake()->createWithContent(
        'perusahaan.csv',
        "nama,alamat,email\nPT Impor,Jl Impor,impor@pt.test\nPT Rusak,,duplikat@pt.test\n",
    );

    Livewire\Livewire::test(ManageCompanies::class)
        ->callAction('import', data: ['file' => $file])
        ->assertHasNoFormErrors();

    expect(Company::query()->where('nama', 'PT Impor')->exists())->toBeTrue()
        ->and(Company::query()->where('nama', 'PT Rusak')->exists())->toBeFalse();
});

test('action impor akun memasukkan akun siswa dengan peran', function () {
    $jurusan = Jurusan::factory()->create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak']);
    Kelas::factory()->create(['jurusan_id' => $jurusan->id, 'nama' => 'XI RPL 1']);

    $file = UploadedFile::fake()->createWithContent(
        'akun.csv',
        "nis_nip,name,email,role,jurusan,kelas\n11110001,Siswa Impor,,siswa,RPL,XI RPL 1\n",
    );

    Livewire\Livewire::test(ManageUsers::class)
        ->callAction('import', data: ['file' => $file])
        ->assertHasNoFormErrors();

    expect(User::query()->where('nis_nip', '11110001')->exists())->toBeTrue();
});
