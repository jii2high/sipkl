<?php

namespace App\Policies;

use App\Models\User;
use Closure;

/**
 * Registri otorisasi unduhan file private per prefix path (TASKS M0.10).
 *
 * Setiap modul mendaftarkan pemeriksa untuk prefix yang dia miliki, mis.
 * `PrivateFilePolicy::register('3/balasan/', fn (User $u, string $p) => ...)`.
 * Tanpa registrasi yang cocok → akses ditolak (default deny).
 */
final class PrivateFilePolicy
{
    /**
     * @var array<string, Closure(User, string): bool>
     */
    private static array $checkers = [];

    /**
     * @param  Closure(User, string): bool  $checker
     */
    public static function register(string $prefix, Closure $checker): void
    {
        self::$checkers[$prefix] = $checker;
    }

    public static function allows(User $user, string $path): bool
    {
        foreach (self::$checkers as $prefix => $checker) {
            if (str_starts_with($path, $prefix)) {
                return (bool) $checker($user, $path);
            }
        }

        return false;
    }

    /**
     * Dipakai test agar registri tidak bocor antar-test.
     */
    public static function reset(): void
    {
        self::$checkers = [];
    }
}
