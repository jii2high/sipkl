<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicPeriod>
 */
class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    public function definition(): array
    {
        $tahun = fake()->unique()->numberBetween(2026, 2035);

        return [
            'tahun_ajaran' => $tahun.'/'.($tahun + 1),
            'batch' => fake()->randomElement(['1', '2']),
            'tanggal_mulai' => $tahun.'-07-01',
            'tanggal_selesai' => $tahun.'-12-31',
            'is_active' => true,
            'locked_for_new_submission' => false,
        ];
    }
}
