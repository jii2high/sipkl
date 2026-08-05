<?php

namespace Database\Factories;

use App\Models\KunjunganMonitoring;
use App\Models\PenugasanPembimbing;
use App\Models\SuratTugasMonitoring;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KunjunganMonitoring>
 */
class KunjunganMonitoringFactory extends Factory
{
    protected $model = KunjunganMonitoring::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'penugasan_id' => PenugasanPembimbing::factory(),
            'surat_tugas_id' => SuratTugasMonitoring::factory(),
            'tanggal_kunjungan' => now()->toDateString(),
            'catatan' => null,
            'dokumentasi_path' => null,
        ];
    }
}
