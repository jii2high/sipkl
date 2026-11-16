<?php

use App\Models\AcademicPeriod;
use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('membuat periode akademik yang valid', function () {
    $period = AcademicPeriod::create([
        'tahun_ajaran' => '2026/2027',
        'batch' => '1',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2026-12-31',
    ]);

    expect($period->exists)->toBeTrue()
        ->and($period->tahun_ajaran)->toBe('2026/2027')
        ->and($period->batch)->toBe('1');
});

it('menolak tahun_ajaran dengan format salah di level database', function () {
    expect(fn () => AcademicPeriod::create([
        'tahun_ajaran' => '2026-2027',
        'batch' => '1',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2026-12-31',
    ]))->toThrow(QueryException::class);
});

it('menolak tanggal_selesai lebih awal dari tanggal_mulai', function () {
    expect(fn () => AcademicPeriod::create([
        'tahun_ajaran' => '2027/2028',
        'batch' => '2',
        'tanggal_mulai' => '2026-12-31',
        'tanggal_selesai' => '2026-07-01',
    ]))->toThrow(QueryException::class);
});

it('menolak periode ganda untuk tahun ajaran dan batch yang sama', function () {
    AcademicPeriod::create([
        'tahun_ajaran' => '2028/2029',
        'batch' => '1',
        'tanggal_mulai' => '2028-07-01',
        'tanggal_selesai' => '2028-12-31',
    ]);

    expect(fn () => AcademicPeriod::create([
        'tahun_ajaran' => '2028/2029',
        'batch' => '1',
        'tanggal_mulai' => '2028-07-01',
        'tanggal_selesai' => '2028-12-31',
    ]))->toThrow(QueryException::class);
});

it('menolak tingkat kelas di luar 10 sampai 12', function () {
    $jurusan = Jurusan::factory()->create();

    expect(fn () => Kelas::create([
        'jurusan_id' => $jurusan->id,
        'nama' => 'XIII RPL 1',
        'tingkat' => 13,
    ]))->toThrow(QueryException::class);
});

it('menolak nama kelas ganda dalam satu jurusan', function () {
    $jurusan = Jurusan::factory()->create();
    Kelas::create(['jurusan_id' => $jurusan->id, 'nama' => 'XI RPL 1', 'tingkat' => 11]);

    expect(fn () => Kelas::create(['jurusan_id' => $jurusan->id, 'nama' => 'XI RPL 1', 'tingkat' => 11]))
        ->toThrow(QueryException::class);
});

it('mengizinkan nama kelas sama pada jurusan berbeda', function () {
    $rpl = Jurusan::factory()->create();
    $tkj = Jurusan::factory()->create();

    Kelas::create(['jurusan_id' => $rpl->id, 'nama' => 'XI 1', 'tingkat' => 11]);
    $kelas = Kelas::create(['jurusan_id' => $tkj->id, 'nama' => 'XI 1', 'tingkat' => 11]);

    expect($kelas->exists)->toBeTrue();
});
