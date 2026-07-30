<?php

namespace Database\Factories;

use App\Models\PengajuanPkl;
use App\Models\PeriodePkl;
use App\Models\Perusahaan;
use App\Models\SiswaProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengajuanPkl>
 */
class PengajuanPklFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => SiswaProfile::factory(),
            'perusahaan_id' => Perusahaan::factory(),
            'periode_id' => PeriodePkl::factory(),
            'status' => 'draft',
        ];
    }
}
