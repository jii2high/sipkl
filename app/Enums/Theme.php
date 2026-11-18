<?php

namespace App\Enums;

/**
 * Pilihan tema per akun (DESIGN.md "Perilaku tema"), sama dengan ENUM
 * `users.theme_preference`. Halaman tanpa akun tetap memakai penyimpanan perangkat.
 */
enum Theme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';
}
