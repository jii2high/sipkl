<?php

namespace App\Services;

use App\Models\Company;
use App\Support\CompanyCheck;
use App\Support\ContactNormalizer;

/**
 * Penilai duplikat perusahaan (TRD.md §3.2, BR-F2-1/2). Satu resolver dipakai form
 * Hubin, form pengajuan siswa (T4.1), dan impor (T2.1) supaya aturannya identik:
 *
 * - strict-unique: `email`, `website`, `telp_fax` setelah normalisasi → bentrok
 *   berarti ditolak (KP-F2-1).
 * - soft-warning: nama dan alamat yang mirip (Levenshtein) hanya memunculkan
 *   peringatan agar Hubin memutuskan sendiri.
 */
final class CompanyResolver
{
    /**
     * Periksa data perusahaan mentah. `$ignoreId` mengabaikan baris yang sedang
     * disunting agar tidak dianggap bentrok dengan dirinya sendiri.
     *
     * @param  array<string, mixed>  $input
     */
    public function inspect(array $input, ?int $ignoreId = null): CompanyCheck
    {
        $keys = ContactNormalizer::companyKeys($input);

        return new CompanyCheck(
            strict: $this->strictMatches($keys, $ignoreId),
            warnings: $this->warnings($input, $ignoreId),
        );
    }

    /** Kunci unik yang dinormalisasi, untuk disimpan pada kolom pencocokan. */
    public function keys(array $input): array
    {
        return ContactNormalizer::companyKeys($input);
    }

    /**
     * @param  array{email: ?string, website: ?string, telp_fax: ?string}  $keys
     * @return array<string, Company>
     */
    private function strictMatches(array $keys, ?int $ignoreId): array
    {
        $matches = [];

        foreach ($keys as $field => $value) {
            if ($value === null) {
                continue;
            }

            // Perusahaan hasil merge tetap memegang kunci UNIQUE-nya, jadi tetap
            // dihitung supaya pesan aplikasi sama dengan penolakan database.
            $company = Company::query()
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where($field, $value)
                ->first();

            if ($company !== null) {
                $matches[$field] = $company;
            }
        }

        return $matches;
    }

    /**
     * Peringatan kemiripan nama/alamat (BR-F2-2). Ambang Levenshtein ada di
     * `config/pkl.php` agar tidak menjadi angka ajaib di sini.
     *
     * @param  array<string, mixed>  $input
     * @return array<int, string>
     */
    private function warnings(array $input, ?int $ignoreId): array
    {
        $nama = $this->fold((string) ($input['nama'] ?? ''));
        $alamat = $this->fold((string) ($input['alamat'] ?? ''));

        if ($nama === '') {
            return [];
        }

        $distanceMax = (int) config('pkl.company.similarity_distance_max');
        $ratioMin = (int) config('pkl.company.similarity_ratio_min');
        $warnings = [];

        foreach ($this->candidateQuery($ignoreId)->get(['id', 'nama', 'alamat']) as $company) {
            if ($this->isSimilar($nama, $this->fold((string) $company->nama), $distanceMax, $ratioMin)) {
                $warnings[] = __('pkl.company.similar_name', ['nama' => $company->nama]);

                continue;
            }

            if ($alamat !== '' && $this->isSimilar($alamat, $this->fold((string) $company->alamat), $distanceMax, $ratioMin)) {
                $warnings[] = __('pkl.company.similar_address', ['nama' => $company->nama]);
            }
        }

        return $warnings;
    }

    private function isSimilar(string $a, string $b, int $distanceMax, int $ratioMin): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }

        $longest = max(strlen($a), strlen($b));

        if ($longest === 0) {
            return false;
        }

        $distance = levenshtein($a, $b);

        if ($distance <= $distanceMax) {
            return true;
        }

        return (1 - $distance / $longest) * 100 >= $ratioMin;
    }

    /** Baris yang masih berdiri sendiri (bukan hasil merge), untuk peringatan. */
    private function candidateQuery(?int $ignoreId)
    {
        return Company::query()
            ->whereNull('merged_into_id')
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId));
    }

    /** Huruf kecil, tanda baca diseragamkan jadi spasi tunggal. */
    private function fold(string $value): string
    {
        $value = strtolower(trim($value));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value);
    }
}
