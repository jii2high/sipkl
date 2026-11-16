<?php

namespace Database\Factories;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code_hash' => hash('sha256', Str::random(32)),
            'purpose' => 'first_login',
            'attempt_count' => 0,
            'expires_at' => now()->addMinute(),
            'invalidated_at' => null,
            'verified_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
