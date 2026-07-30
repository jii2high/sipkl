<?php

namespace Database\Factories;

use App\Models\KompetensiKeahlian;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KompetensiKeahlian> */
class KompetensiKeahlianFactory extends Factory
{
    protected $model = KompetensiKeahlian::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->word().' '.fake()->year(),
            'acronym' => fake()->unique()->lexify('???'),
        ];
    }
}
