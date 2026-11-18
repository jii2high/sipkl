<?php

use App\Actions\Master\ImportData;
use App\Models\Company;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;

test('impor 100 baris dengan 5 baris salah memasukkan 95 baris dan melaporkan 5', function () {
    $hubin = pklUser(['hubin']);
    $badRows = [7, 18, 29, 41, 88];
    $rows = [];

    for ($i = 1; $i <= 100; $i++) {
        $rows[] = [
            'nama' => "PT Contoh {$i}",
            'alamat' => in_array($i, $badRows, true) ? '' : "Jl. Contoh {$i}",
            'email' => "pt{$i}@contoh.test",
            'telp_fax' => '0215'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
        ];
    }

    $report = app(ImportData::class)->handle(ImportData::KIND_COMPANIES, $rows, $hubin);

    expect($report->total)->toBe(100)
        ->and($report->imported)->toBe(95)
        ->and($report->failedCount())->toBe(5)
        ->and($report->failed)->toHaveCount(5)
        ->and(Company::query()->count())->toBe(95);

    expect(array_column($report->failed, 'row'))->toBe($badRows);
});

test('baris perusahaan dengan email duplikat dilaporkan, baris lain tetap masuk', function () {
    $hubin = pklUser(['hubin']);
    Company::factory()->create(['email' => 'ada@pt-test.co.id']);

    $report = app(ImportData::class)->handle(ImportData::KIND_COMPANIES, [
        ['nama' => 'PT Satu', 'alamat' => 'Jl. Satu', 'email' => 'baru@pt-test.co.id'],
        ['nama' => 'PT Dua', 'alamat' => 'Jl. Dua', 'email' => 'ada@pt-test.co.id'],
        ['nama' => 'PT Tiga', 'alamat' => 'Jl. Tiga', 'email' => 'tiga@pt-test.co.id'],
    ], $hubin);

    expect($report->imported)->toBe(2)
        ->and($report->failed[0]['row'])->toBe(2)
        ->and(Company::query()->count())->toBe(3);
});

test('impor akun membuat pengguna beserta peran dan scope-nya', function () {
    $hubin = pklUser(['hubin']);
    $rpl = Jurusan::factory()->create(['kode' => 'RPL']);
    Kelas::factory()->create(['jurusan_id' => $rpl->id, 'nama' => '10 RPL 1', 'tingkat' => 10]);

    $report = app(ImportData::class)->handle(ImportData::KIND_ACCOUNTS, [
        ['nis_nip' => '240001', 'name' => 'Siswa Satu', 'role' => 'siswa', 'jurusan' => 'RPL', 'kelas' => '10 RPL 1'],
        ['nis_nip' => '197001', 'name' => 'Walas', 'role' => 'walas', 'kelas' => '10 RPL 1'],
        ['nis_nip' => '197002', 'name' => 'Kaprog', 'role' => 'kaprog', 'jurusan' => 'RPL'],
        ['nis_nip' => '197003', 'name' => 'Guru', 'role' => 'guru_pembimbing'],
    ], $hubin);

    expect($report->imported)->toBe(4)->and($report->failedCount())->toBe(0);

    $siswa = User::query()->where('nis_nip', '240001')->firstOrFail();
    expect($siswa->user_type)->toBe('siswa')
        ->and($siswa->is_first_login)->toBeTrue()
        ->and($siswa->jurusan_id)->toBe($rpl->id);

    $kaprog = UserRole::query()->where('role', 'kaprog')->firstOrFail();
    $walas = UserRole::query()->where('role', 'walas')->firstOrFail();

    expect($kaprog->jurusan_id)->toBe($rpl->id)
        ->and($walas->kelas_id)->not->toBeNull();
});

test('impor akun menolak baris dengan peran atau scope tidak sah', function () {
    $hubin = pklUser(['hubin']);
    $rpl = Jurusan::factory()->create(['kode' => 'RPL']);

    $report = app(ImportData::class)->handle(ImportData::KIND_ACCOUNTS, [
        ['nis_nip' => '240010', 'name' => 'Siswa Tanpa Kelas', 'role' => 'siswa', 'jurusan' => 'RPL'],
        ['nis_nip' => '197010', 'name' => 'Kaprog Tanpa Jurusan', 'role' => 'kaprog'],
        ['nis_nip' => '197011', 'name' => 'Peran Asing', 'role' => 'kepala_suku'],
        ['nis_nip' => '197012', 'name' => 'Jurusan Asing', 'role' => 'kaprog', 'jurusan' => 'TKR'],
        ['nis_nip' => '197013', 'name' => 'Kaprog Sah', 'role' => 'kaprog', 'jurusan' => 'RPL'],
    ], $hubin);

    expect($report->imported)->toBe(1)
        ->and($report->failedCount())->toBe(4)
        ->and(User::query()->where('nis_nip', '197013')->exists())->toBeTrue();
});
