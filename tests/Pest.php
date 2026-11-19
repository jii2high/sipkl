<?php

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest
|--------------------------------------------------------------------------
| Tes Feature memakai aplikasi + database MySQL nyata (sistem_pkl_test) dan
| di-reset per tes. Tes Unit berjalan tanpa database.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Buat pengguna beserta perannya. Scope jurusan/kelas diisi bila peran
 * mewajibkannya (CHECK `ck_role_scope`: kaprog butuh jurusan, walas butuh kelas).
 *
 * @param  array<int, string>  $roles
 * @param  array<string, mixed>  $attributes
 */
function pklUser(array $roles = ['guru_pembimbing'], array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    foreach ($roles as $role) {
        $scope = match ($role) {
            'kaprog' => ['jurusan_id' => Jurusan::factory()->create()->id],
            'walas' => ['kelas_id' => Kelas::factory()->create()->id],
            default => [],
        };

        UserRole::query()->create(['user_id' => $user->id, 'role' => $role] + $scope);
    }

    return $user;
}
