<?php

namespace App\Support;

/**
 * Penjaga signer industri (BR-F2-3, KP-F2-4). Signer Pembimbing/Kepala Industri
 * harus nama individu dengan email terverifikasi; jabatan generik ("HRD", "Admin",
 * "Kepala Bagian") tidak boleh dipakai sebagai penerima OTP. Dipakai form Hubin
 * sekarang dan gerbang step 3/4 (T6.3) nanti.
 */
final class SignerGuard
{
    public static function isGeneric(?string $nama): bool
    {
        $tokens = preg_split('/\s+/', self::normalize($nama)) ?: [];

        foreach ((array) config('pkl.signer.generic_tokens') as $generic) {
            if (in_array($generic, $tokens, true)) {
                return true;
            }
        }

        return false;
    }

    /** Signer boleh menerima OTP: nama individu, email terisi, email terverifikasi. */
    public static function canReceiveOtp(?string $nama, ?string $email, mixed $verifiedAt): bool
    {
        return filled($nama)
            && filled($email)
            && $verifiedAt !== null
            && ! self::isGeneric($nama);
    }

    /** Huruf kecil, hanya huruf dan spasi tunggal. */
    private static function normalize(?string $nama): string
    {
        $value = strtolower((string) $nama);
        $value = preg_replace('/[^a-z]+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
