<?php

namespace Database\Factories;

use App\Models\PeriodePkl;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PeriodePkl> */
class PeriodePklFactory extends Factory
{
    protected $model = PeriodePkl::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->word().' '.fake()->year(),
            'tanggal_mulai' => fake()->date(),
            'tanggal_selesai' => fake()->date(),
            'status_aktif' => false,
        ];
    }
}
