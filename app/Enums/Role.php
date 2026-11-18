<?php

namespace App\Enums;

/**
 * Peran pengguna, sama persis dengan ENUM `user_roles.role` (docs/schema.sql).
 * Satu pengguna dapat memegang lebih dari satu peran; peran aktif dipilih lewat
 * `SwitchActiveRole` dan disimpan di sesi (TRD.md §7.2).
 */
enum Role: string
{
    case Siswa = 'siswa';
    case Walas = 'walas';
    case GuruPembimbing = 'guru_pembimbing';
    case Kaprog = 'kaprog';
    case Hubin = 'hubin';
    case Kepsek = 'kepsek';

    /** Label Bahasa Indonesia untuk pemilih peran dan chip. */
    public function label(): string
    {
        return match ($this) {
            self::Siswa => 'Siswa',
            self::Walas => 'Wali Kelas',
            self::GuruPembimbing => 'Guru Pembimbing',
            self::Kaprog => 'Kepala Program',
            self::Hubin => 'Hubin',
            self::Kepsek => 'Kepala Sekolah',
        };
    }

    /** Urutan prioritas saat memilih peran aktif default setelah login. */
    public static function defaultPriority(): array
    {
        return [self::Siswa, self::Hubin, self::GuruPembimbing, self::Walas, self::Kaprog, self::Kepsek];
    }
}
