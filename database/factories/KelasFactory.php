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

    public function definition(): array
    {
        return [
            'jurusan_id' => Jurusan::factory(),
            'nama' => 'Kelas '.fake()->unique()->bothify('##?'),
            'tingkat' => fake()->randomElement([10, 11, 12]),
        ];
    }
}
