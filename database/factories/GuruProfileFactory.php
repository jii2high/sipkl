<?php

namespace Database\Factories;

use App\Models\GuruProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GuruProfile> */
class GuruProfileFactory extends Factory
{
    protected $model = GuruProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nip' => fake()->unique()->numerify('19##############'),
            'jabatan' => 'guru_pembimbing',
            'kompetensi_keahlian_id' => null,
        ];
    }
}
