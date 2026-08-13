<?php

namespace Database\Factories;

use App\Models\KompetensiKeahlian;
use App\Models\PeriodePkl;
use App\Models\SuratPengantar;
use App\Models\User;
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
            'kompetensi_keahlian_id' => KompetensiKeahlian::factory(),
            'periode_id' => PeriodePkl::factory(),
            'tanggal_terbit' => now(),
            'generated_by' => User::factory(),
        ];
    }
}
