<?php

namespace Database\Factories;

use App\Models\IndikatorPenilaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IndikatorPenilaian> */
class IndikatorPenilaianFactory extends Factory
{
    protected $model = IndikatorPenilaian::class;

    public function definition(): array
    {
        return [
            'kompetensi_keahlian_id' => null,
            'nomor_urut' => fake()->numberBetween(1, 20),
            'nama' => fake()->sentence(3),
        ];
    }
}
