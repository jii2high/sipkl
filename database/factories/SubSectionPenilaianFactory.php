<?php

namespace Database\Factories;

use App\Models\SectionPenilaian;
use App\Models\SubSectionPenilaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SubSectionPenilaian> */
class SubSectionPenilaianFactory extends Factory
{
    protected $model = SubSectionPenilaian::class;

    public function definition(): array
    {
        return [
            'section_penilaian_id' => SectionPenilaian::factory(),
            'nomor_urut' => 1,
            'nama' => fake()->word(),
        ];
    }
}
