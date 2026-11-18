<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tingkat = fake()->randomElement([10, 11, 12]);

        return [
            'jurusan_id' => Jurusan::factory(),
            'nama' => $tingkat.' '.fake()->unique()->lexify('??').' 1',
            'tingkat' => $tingkat,
        ];
    }
}
