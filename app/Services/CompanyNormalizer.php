<?php

namespace App\Services;

/**
 * Normalisasi data perusahaan sebelum disimpan (TASKS M2.2).
 *
 * Aturan (PRD A2/PD-16):
 * - email: trim + lowercase.
 * - website: trim + lowercase, buang skema (https://, http://), awalan `www.`,
 *   dan garis miring di akhir.
 * - telp_fax: hanya digit (spasi, strip, kurung, dan titik dibuang).
 *
 * Nilai kosong setelah normalisasi dikembalikan sebagai NULL supaya UNIQUE
 * tidak memblokir banyak perusahaan yang memang tidak punya email/website/telp.
 */
final class CompanyNormalizer
{
    /**
     * Normalisasi satu kolom; kolom yang tidak dikenal dikembalikan apa adanya.
     */
    public static function column(string $column, ?string $value): ?string
    {
        return match ($column) {
            'email' => self::email($value),
            'website' => self::website($value),
            'telp_fax' => self::telp($value),
            default => $value,
        };
    }

    public static function email(?string $value): ?string
    {
        $value = self::bersihkan($value);

        return $value === null ? null : mb_strtolower($value);
    }

    public static function website(?string $value): ?string
    {
        $value = self::bersihkan($value);

        if ($value === null) {
            return null;
        }

        $value = mb_strtolower($value);
        $value = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $value) ?? $value;
        $value = preg_replace('#^www\.#', '', $value) ?? $value;
        $value = rtrim($value, '/');

        return $value === '' ? null : $value;
    }

    public static function telp(?string $value): ?string
    {
        $value = self::bersihkan($value);

        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits === '' ? null : $digits;
    }

    /**
     * Normalisasi array atribut perusahaan. Hanya kunci yang ada yang disentuh,
     * sehingga aman dipakai untuk update parsial.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function attributes(array $attributes): array
    {
        foreach (['email', 'website', 'telp_fax'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $value = $attributes[$column];

                $attributes[$column] = is_string($value) || $value === null
                    ? self::column($column, $value)
                    : $value;
            }
        }

        return $attributes;
    }

    private static function bersihkan(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
