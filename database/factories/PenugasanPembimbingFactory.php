<?php

namespace Database\Factories;

use App\Models\PengajuanPkl;
use App\Models\PenugasanPembimbing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenugasanPembimbing>
 */
class PenugasanPembimbingFactory extends Factory
{
    protected $model = PenugasanPembimbing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guru_pembimbing_id' => User::factory(),
            'pengajuan_id' => PengajuanPkl::factory(),
            'assigned_by' => User::factory(),
            'status' => 'aktif',
        ];
    }
}
