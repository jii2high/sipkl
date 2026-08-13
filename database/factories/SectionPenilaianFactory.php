<?php

namespace Database\Factories;

use App\Models\IndikatorPenilaian;
use App\Models\SectionPenilaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SectionPenilaian> */
class SectionPenilaianFactory extends Factory
{
    protected $model = SectionPenilaian::class;

    public function definition(): array
    {
        return [
            'indikator_penilaian_id' => IndikatorPenilaian::factory(),
            'nomor_urut' => fake()->numberBetween(1, 10),
            'nama' => fake()->word(),
        ];
    }
}
