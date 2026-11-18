<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Collection;

/**
 * Saran perusahaan mirip dengan skor Levenshtein (TASKS M2.3, AC-11).
 *
 * Kandidat diambil murah lewat LIKE (prefix nama + token nama/alamat), lalu
 * diskor di PHP. Method ini HANYA menyarankan — tidak pernah menolak input.
 * Perusahaan yang sudah di-merge tidak pernah disarankan.
 */
final class CompanyResolver
{
    private readonly float $threshold;

    private readonly int $maxCandidates;

    private readonly int $limit;

    public function __construct(?float $threshold = null, ?int $maxCandidates = null, ?int $limit = null)
    {
        $this->threshold = $threshold ?? (float) config('company.similarity_threshold', 0.72);
        $this->maxCandidates = $maxCandidates ?? (int) config('company.max_candidates', 100);
        $this->limit = $limit ?? (int) config('company.max_suggestions', 5);
    }

    /**
     * @return Collection<int, array{perusahaan: Company, skor: float}>
     */
    public function suggest(string $nama, ?string $alamat = null): Collection
    {
        $nama = trim($nama);

        if ($nama === '' || $this->limit <= 0) {
            return collect();
        }

        $tokens = $this->tokens($nama.' '.($alamat ?? ''));

        if ($tokens === []) {
            return collect();
        }

        $kandidat = Company::query()
            ->belumMerge()
            ->where(function ($query) use ($tokens, $nama) {
                $query->where('nama', 'like', $this->escapeLike($nama).'%');

                // Token utuh tidak cukup untuk salah ketik di tengah kata
                // ("Ajikyo" vs "Ajikiyo"), jadi kandidat diambil lewat prefiks
                // 4 huruf pertama dan skor Levenshtein yang menyaring sisanya.
                foreach ($tokens as $token) {
                    $prefix = mb_substr($token, 0, 4);

                    $query->orWhere('nama', 'like', '%'.$this->escapeLike($prefix).'%')
                        ->orWhere('alamat', 'like', '%'.$this->escapeLike($prefix).'%');
                }
            })
            ->orderBy('id')
            ->limit($this->maxCandidates)
            ->get(['id', 'nama', 'alamat', 'email', 'website', 'telp_fax', 'merged_into_id']);

        return $kandidat
            ->map(fn (Company $company): array => [
                'perusahaan' => $company,
                'skor' => round($this->skor($nama, $alamat, $company), 4),
            ])
            ->filter(fn (array $saran): bool => $saran['skor'] >= $this->threshold)
            ->sortByDesc('skor')
            ->take($this->limit)
            ->values();
    }

    /**
     * Skor 0..1: kemiripan nama, dengan bonus kecil bila alamat juga mirip.
     */
    public function skor(string $nama, ?string $alamat, Company $company): float
    {
        $skorNama = $this->kemiripan($this->rapikan($nama), $this->rapikan((string) $company->nama));

        if ($skorNama === 0.0) {
            return 0.0;
        }

        $alamat = $alamat === null ? '' : trim($alamat);

        if ($alamat === '' || $company->alamat === null) {
            return $skorNama;
        }

        $skorAlamat = $this->kemiripan($this->rapikan($alamat), $this->rapikan((string) $company->alamat));

        if ($skorAlamat < 0.8) {
            return $skorNama;
        }

        return min(1.0, $skorNama + 0.05);
    }

    /**
     * 1 - (jarak Levenshtein / panjang terpanjang), dibatasi 0..1.
     */
    private function kemiripan(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        $panjang = max(strlen($a), strlen($b));

        return max(0.0, 1.0 - (levenshtein($a, $b) / $panjang));
    }

    private function rapikan(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /**
     * @return list<string>
     */
    private function tokens(string $value): array
    {
        $kata = preg_split('/\s+/', $this->rapikan($value)) ?: [];

        return array_values(array_unique(array_filter(
            $kata,
            fn (string $token): bool => mb_strlen($token) >= 4,
        )));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
