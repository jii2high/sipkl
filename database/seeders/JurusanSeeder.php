<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

/**
 * Jurusan dan kelas awal. Daftar jurusan mengikuti contoh di docs/schema.sql;
 * daftar resmi sekolah dikonfirmasi ke Hubin (lihat Catatan Pelaksanaan TASKS.md).
 */
class JurusanSeeder extends Seeder
{
    /** @var array<string, string> */
    private const JURUSAN = [
        'RPL' => 'Rekayasa Perangkat Lunak',
        'TKR' => 'Teknik Kendaraan Ringan',
        'TP' => 'Teknik Pemesinan',
    ];

    public function run(): void
    {
        foreach (self::JURUSAN as $kode => $nama) {
            $jurusan = Jurusan::query()->firstOrCreate(['kode' => $kode], ['nama' => $nama]);

            foreach ([10, 11, 12] as $tingkat) {
                Kelas::query()->firstOrCreate(
                    ['jurusan_id' => $jurusan->id, 'nama' => "{$tingkat} {$kode} 1"],
                    ['tingkat' => $tingkat],
                );
            }
        }
    }
}
