<?php

namespace Database\Factories;

use App\Models\Jurusan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Jurusan>
 */
class JurusanFactory extends Factory
{
    protected $model = Jurusan::class;

    public function definition(): array
    {
        return [
            'kode' => Str::upper(fake()->unique()->bothify('??#')),
            'nama' => 'Jurusan '.fake()->unique()->words(2, true),
        ];
    }
}
