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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tahun_ajaran' => '2025/2026',
            'batch' => fake()->randomElement(['1', '2']),
            'tanggal_mulai' => '2026-01-05',
            'tanggal_selesai' => '2026-04-30',
            'is_active' => true,
            'locked_for_new_submission' => false,
        ];
    }
}
