<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRole>
 */
class UserRoleFactory extends Factory
{
    protected $model = UserRole::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role' => 'guru_pembimbing',
            'jurusan_id' => null,
            'kelas_id' => null,
        ];
    }

    public function kaprog(): static
    {
        return $this->state(fn () => [
            'role' => 'kaprog',
            'jurusan_id' => Jurusan::factory(),
            'kelas_id' => null,
        ]);
    }

    public function walas(): static
    {
        return $this->state(fn () => [
            'role' => 'walas',
            'jurusan_id' => null,
            'kelas_id' => Kelas::factory(),
        ]);
    }

    public function siswa(): static
    {
        return $this->state(fn () => [
            'role' => 'siswa',
        ]);
    }
}
