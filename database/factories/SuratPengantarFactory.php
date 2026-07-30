<?php

namespace Database\Factories;

use App\Models\SuratPengantar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuratPengantar>
 */
class SuratPengantarFactory extends Factory
{
    protected $model = SuratPengantar::class;

    public function definition(): array
    {
        return [
            'nomor_surat' => fake()->unique()->numerify('1/######/00/00/00/0000'),
            'tanggal_terbit' => now(),
        ];
    }
}
