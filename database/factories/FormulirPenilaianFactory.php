<?php

namespace Database\Factories;

use App\Models\FormulirPenilaian;
use App\Models\PenugasanPembimbing;
use App\Models\PeriodePkl;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormulirPenilaian>
 */
class FormulirPenilaianFactory extends Factory
{
    protected $model = FormulirPenilaian::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'penugasan_id' => PenugasanPembimbing::factory(),
            'siswa_id' => User::factory(),
            'periode_pkl_id' => PeriodePkl::factory(),
            'guru_pembimbing_id' => User::factory(),
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
        ];
    }
}
