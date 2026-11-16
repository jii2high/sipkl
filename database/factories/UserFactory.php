<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Password awal = NIS/NIP (di-hash otomatis oleh cast `hashed`).
     */
    public function definition(): array
    {
        $nisNip = fake()->unique()->numerify(str_repeat('#', 6));

        return [
            'nis_nip' => $nisNip,
            'name' => fake()->name(),
            'email' => fake()->unique()->userName().'@smkn1kotabekasi.sch.id',
            'email_verified_at' => now(),
            'password' => $nisNip,
            'user_type' => 'guru',
            'jurusan_id' => null,
            'kelas_id' => null,
            'is_first_login' => true,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function siswa(): static
    {
        return $this->state(fn () => [
            'user_type' => 'siswa',
            'jurusan_id' => Jurusan::factory(),
            'kelas_id' => Kelas::factory(),
        ]);
    }

    public function guru(): static
    {
        return $this->state(fn () => [
            'user_type' => 'guru',
        ]);
    }

    public function hubin(): static
    {
        return $this->state(fn () => [
            'user_type' => 'hubin',
        ]);
    }

    public function kepsek(): static
    {
        return $this->state(fn () => [
            'user_type' => 'kepsek',
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function sudahGantiPassword(): static
    {
        return $this->state(fn () => [
            'is_first_login' => false,
        ]);
    }
}
