<?php

namespace Database\Factories;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<PasswordHistory> */
class PasswordHistoryFactory extends Factory
{
    protected $model = PasswordHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'password' => Hash::make('password-lama'),
            'created_at' => fake()->dateTimeBetween('-6 months', '-1 month'),
        ];
    }
}
