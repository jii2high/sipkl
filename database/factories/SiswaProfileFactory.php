<?php

namespace Database\Factories;

use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\SiswaProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiswaProfile> */
class SiswaProfileFactory extends Factory
{
    protected $model = SiswaProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nis' => fake()->unique()->numerify('##########'),
            'periode_id' => PeriodePkl::factory(),
            'kompetensi_keahlian_id' => KompetensiKeahlian::factory(),
        ];
    }
}
