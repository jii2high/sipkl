<?php

namespace App\Rules;

use App\Models\Company;
use App\Services\CompanyNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

/**
 * Keunikan strict email/website/telp perusahaan (TASKS M2.2, AC-12).
 *
 * Nilai dibandingkan dalam bentuk ternormalisasi, record yang sudah di-merge
 * (`merged_into_id` terisi) diabaikan, dan record milik sendiri bisa dikecualikan
 * saat update lewat `$ignoreId`.
 */
final class CompanyUniqueStrict implements ValidationRule
{
    private const KOLOM = ['email', 'website', 'telp_fax'];

    public function __construct(
        private readonly string $column,
        private readonly ?int $ignoreId = null,
    ) {
        if (! in_array($column, self::KOLOM, true)) {
            throw new InvalidArgumentException("Kolom [{$column}] tidak didukung CompanyUniqueStrict.");
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && ! is_string($value)) {
            return;
        }

        $normalized = CompanyNormalizer::column($this->column, $value);

        if ($normalized === null) {
            return;
        }

        $sudahAda = Company::query()
            ->where($this->column, $normalized)
            ->whereNull('merged_into_id')
            ->when(
                $this->ignoreId !== null,
                fn ($query) => $query->whereKeyNot($this->ignoreId),
            )
            ->exists();

        if ($sudahAda) {
            $fail($this->pesan());
        }
    }

    private function pesan(): string
    {
        return match ($this->column) {
            'email' => 'Email perusahaan ini sudah terdaftar.',
            'website' => 'Website perusahaan ini sudah terdaftar.',
            'telp_fax' => 'Nomor telepon/fax perusahaan ini sudah terdaftar.',
        };
    }
}
