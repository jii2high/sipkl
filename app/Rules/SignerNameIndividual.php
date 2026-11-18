<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nama signer industri harus nama individu, bukan jabatan generik (TASKS M2.8, AC-52).
 */
final class SignerNameIndividual implements ValidationRule
{
    /**
     * Jabatan generik yang tidak boleh dipakai sebagai nama signer.
     *
     * @var list<string>
     */
    private const GENERIK = [
        'pembimbing',
        'pembimbing industri',
        'pembimbing pkl',
        'kepala',
        'kepala industri',
        'kepala pkl',
        'pimpinan',
        'manajer',
        'manager',
        'direktur',
        'hrd',
        'hr',
        'hrd department',
        'human resource',
        'human resources',
        'admin',
        'administrasi',
        'staf',
        'staff',
        'karyawan',
        'industri',
        'perusahaan',
        'pic',
        'contact person',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Nama signer wajib diisi dengan nama individu.');

            return;
        }

        $normal = mb_strtolower(trim($value));
        $normal = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $normal) ?? $normal;
        $normal = trim(preg_replace('/\s+/', ' ', $normal) ?? $normal);

        if (in_array($normal, self::GENERIK, true)) {
            $fail('Nama signer tidak boleh jabatan generik. Tulis nama individu yang menandatangani.');

            return;
        }

        if (mb_strlen($normal) < 3) {
            $fail('Nama signer terlalu pendek. Tulis nama individu yang menandatangani.');
        }
    }
}
