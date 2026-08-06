<?php

namespace Database\Factories;

use App\Models\FormulirPenilaian;
use App\Models\IndikatorPenilaian;
use App\Models\NilaiPenilaian;
use App\Models\SectionPenilaian;
use App\Support\SkalaPredikat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NilaiPenilaian>
 */
class NilaiPenilaianFactory extends Factory
{
    protected $model = NilaiPenilaian::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nilai = fake()->randomFloat(0, 40, 100);

        return [
            'formulir_penilaian_id' => FormulirPenilaian::factory(),
            'indikator_penilaian_id' => IndikatorPenilaian::factory(),
            'section_penilaian_id' => null,
            'nilai' => $nilai,
            'predikat' => SkalaPredikat::dari($nilai),
        ];
    }

    public function untukSection(SectionPenilaian $section): static
    {
        $nilai = fake()->randomFloat(0, 40, 100);

        return $this->state([
            'indikator_penilaian_id' => null,
            'section_penilaian_id' => $section->id,
            'nilai' => $nilai,
            'predikat' => SkalaPredikat::dari($nilai),
        ]);
    }
}
