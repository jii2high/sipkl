<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis_nip' => (string) fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'email' => 'user'.strtolower(Str::random(8)).'@smkn1kotabekasi.sch.id',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'user_type' => 'guru',
            'is_first_login' => true,
            'is_active' => true,
        ];
    }

    /** Siswa wajib punya jurusan dan kelas (CHECK `ck_users_siswa_scope`). */
    public function siswa(int $jurusanId, int $kelasId): static
    {
        return $this->state(fn () => [
            'user_type' => 'siswa',
            'jurusan_id' => $jurusanId,
            'kelas_id' => $kelasId,
        ]);
    }
}
