<?php

namespace App\Support;

/**
 * Normalisasi kontak perusahaan (TRD.md §3.2, BR-F2-1). Dipakai satu kali di
 * `CompanyResolver` sehingga form Hubin, form siswa, dan impor menilai keunikan
 * dengan aturan yang sama. Kolom `email`, `website`, dan `telp_fax` menyimpan
 * bentuk yang sudah dinormalisasi, karena kunci UNIQUE database harus membandingkan
 * nilai yang setara; bentuk asli yang diketik pengguna tidak disimpan.
 */
final class ContactNormalizer
{
    /** Email: huruf kecil tanpa spasi tepi. */
    public static function email(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return $value === '' ? null : $value;
    }

    /**
     * Website: tanpa skema (`http://`, `https://`), tanpa `www.`, tanpa garis miring
     * di ujung, huruf kecil. `https://WWW.PT-Test.co.id/` dan `pt-test.co.id`
     * dianggap sama.
     */
    public static function website(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('#^[a-z][a-z0-9+.\-]*://#', '', $value) ?? $value;
        $value = preg_replace('#^www\.#', '', $value) ?? $value;
        $value = rtrim($value, '/');

        return $value === '' ? null : $value;
    }

    /**
     * Telepon/faks: hanya digit; awalan negara `62` menjadi `0`. Format, spasi,
     * tanda hubung, dan kurung dibuang karena bukan bagian identitas nomor.
     */
    public static function phone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Normalisasi kolom kunci keunikan perusahaan dari data mentah.
     *
     * @param  array<string, mixed>  $input
     * @return array{email: ?string, website: ?string, telp_fax: ?string}
     */
    public static function companyKeys(array $input): array
    {
        return [
            'email' => self::email($input['email'] ?? null),
            'website' => self::website($input['website'] ?? null),
            'telp_fax' => self::phone($input['telp_fax'] ?? null),
        ];
    }
}
