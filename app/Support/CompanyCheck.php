<?php

namespace App\Support;

use App\Models\Company;

/**
 * Hasil pemeriksaan `CompanyResolver` untuk satu set data perusahaan.
 * `strict` berisi bentrok kunci unik (harus ditolak, BR-F2-1); `warnings` berisi
 * kemiripan nama/alamat yang hanya perlu dikonfirmasi manusia (BR-F2-2).
 */
final class CompanyCheck
{
    /**
     * @param  array<string, Company>  $strict  nama kolom => perusahaan yang sudah memakai nilai itu
     * @param  array<int, string>  $warnings  pesan peringatan siap tampil (Bahasa Indonesia)
     */
    public function __construct(
        public readonly array $strict = [],
        public readonly array $warnings = [],
    ) {}

    public function hasStrictConflicts(): bool
    {
        return $this->strict !== [];
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /** Ada kemiripan atau bentrok yang perlu diperlihatkan ke pengguna? */
    public function needsAttention(): bool
    {
        return $this->hasStrictConflicts() || $this->hasWarnings();
    }

    /** Pesan bentrok kunci unik, mis. `email` => "Email ... sudah dipakai ...". */
    public function strictMessage(string $field): ?string
    {
        $company = $this->strict[$field] ?? null;

        if ($company === null) {
            return null;
        }

        return __('pkl.company.duplicate', [
            'field' => __('pkl.company.field.'.$field),
            'nama' => $company->nama,
        ]);
    }
}
