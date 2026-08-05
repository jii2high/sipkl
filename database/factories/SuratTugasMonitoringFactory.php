<?php

namespace Database\Factories;

use App\Models\PeriodePkl;
use App\Models\SuratTugasMonitoring;
use App\Models\User;
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
            'diterbitkan_by' => User::factory(),
            'nomor_surat' => fake()->unique()->numerify('1/######/00/00/00/0000'),
            'tanggal_terbit' => now(),
            'status' => 'aktif',
            'berlaku_hingga' => now()->addMonth(),
        ];
    }
}
