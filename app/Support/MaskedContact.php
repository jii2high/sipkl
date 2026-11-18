<?php

namespace App\Support;

/**
 * Penyamaran kontak perusahaan. Kontak narahubung (`cp_phone`) dan email signer
 * tidak boleh tampil utuh sebelum disetujui Kaprog (PRD F3 alur 1). Dipakai ulang
 * oleh komponen `MaskedContact` di T4.1.
 */
final class MaskedContact
{
    /** `0812-3456-7890` → `0812******90`; bagian awal dan akhir tetap terbaca. */
    public static function phone(?string $value, int $visibleStart = 4, int $visibleEnd = 2): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $length = mb_strlen($value);

        if ($length <= $visibleStart + $visibleEnd) {
            return mb_substr($value, 0, 1).str_repeat('*', max($length - 1, 1));
        }

        return mb_substr($value, 0, $visibleStart)
            .str_repeat('*', $length - $visibleStart - $visibleEnd)
            .mb_substr($value, $length - $visibleEnd);
    }

    /** `budi.santoso@pt.test` → `bu***********@pt.test`; domain tetap terbaca. */
    public static function email(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || ! str_contains($value, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $value, 2);
        $keep = mb_substr($local, 0, 2);

        return $keep.str_repeat('*', max(mb_strlen($local) - mb_strlen($keep), 1)).'@'.$domain;
    }

    /** `budi.santoso@pt.test` → `bu***@pt.test`; bentuk ringkas untuk tabel. */
    public static function emailShort(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || ! str_contains($value, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $value, 2);

        return mb_substr($local, 0, 2).'***@'.$domain;
    }
}
