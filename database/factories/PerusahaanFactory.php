<?php

namespace Database\Factories;

use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Perusahaan> */
class PerusahaanFactory extends Factory
{
    protected $model = Perusahaan::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->company(),
            'alamat' => fake()->address(),
            'telp' => fake()->phoneNumber(),
            'kuota_saran' => fake()->numberBetween(3, 20),
        ];
    }
}
