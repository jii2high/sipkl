<?php

namespace Database\Factories;

use App\Models\PeriodePkl;
use App\Models\SuratTugasMonitoring;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuratTugasMonitoring>
 */
class SuratTugasMonitoringFactory extends Factory
{
    protected $model = SuratTugasMonitoring::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periode_id' => PeriodePkl::factory(),
            'lingkup' => 'global',
            'kompetensi_keahlian_id' => null,
            'requested_by' => null,
            'diterbitkan_by' => null,
            'nomor_surat' => null,
            'tanggal_terbit' => null,
            'status' => 'draft',
            'berlaku_hingga' => now()->addMonth(),
        ];
    }
}
