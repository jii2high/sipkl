<?php

namespace App\Imports;

use App\Models\Company;
use App\Rules\CompanyUniqueStrict;
use App\Rules\SignerNameIndividual;
use App\Services\CompanyNormalizer;
use App\Services\CompanyResolver;
use Illuminate\Support\Facades\Validator;

/**
 * Impor massal perusahaan (TASKS M2.9).
 *
 * Memakai aturan yang sama dengan form Hubin: normalisasi M2.2, strict-unique
 * email/website/telp (AC-12), dan soft-warning nama/alamat mirip via
 * `CompanyResolver` (AC-11) yang hanya menandai "perlu tinjau".
 */
final class CompaniesImport extends BaseImport
{
    /**
     * @param  array<string, mixed>  $row
     */
    protected function prosesBaris(array $row, int $nomor): void
    {
        $data = [
            'nama' => $this->ambil($row, 'nama_perusahaan', 'nama'),
            'alamat' => $this->ambil($row, 'alamat'),
            'email' => CompanyNormalizer::email($this->ambil($row, 'email')),
            'website' => CompanyNormalizer::website($this->ambil($row, 'website')),
            'telp_fax' => CompanyNormalizer::telp($this->ambil($row, 'telepon_fax', 'teleponfax', 'telp_fax', 'telepon')),
            'cp_name' => $this->ambil($row, 'nama_kontak', 'cp_name'),
            'cp_phone' => CompanyNormalizer::telp($this->ambil($row, 'telepon_kontak', 'cp_phone')),
            'pembimbing_industri_nama' => $this->ambil($row, 'nama_pembimbing_industri', 'pembimbing_industri_nama'),
            'pembimbing_industri_email' => CompanyNormalizer::email($this->ambil($row, 'email_pembimbing_industri', 'pembimbing_industri_email')),
            'kepala_industri_nama' => $this->ambil($row, 'nama_kepala_industri', 'kepala_industri_nama'),
            'kepala_industri_email' => CompanyNormalizer::email($this->ambil($row, 'email_kepala_industri', 'kepala_industri_email')),
        ];

        Validator::make(
            $data,
            [
                'nama' => ['required', 'string', 'max:200'],
                'alamat' => ['required', 'string'],
                'email' => ['nullable', 'email', 'max:150', new CompanyUniqueStrict('email')],
                'website' => ['nullable', 'string', 'max:200', new CompanyUniqueStrict('website')],
                'telp_fax' => ['nullable', 'string', 'max:30', new CompanyUniqueStrict('telp_fax')],
                'cp_name' => ['nullable', 'string', 'max:150'],
                'cp_phone' => ['nullable', 'string', 'max:30'],
                'pembimbing_industri_nama' => ['nullable', 'string', 'max:150', new SignerNameIndividual],
                'pembimbing_industri_email' => ['nullable', 'email', 'max:150'],
                'kepala_industri_nama' => ['nullable', 'string', 'max:150', new SignerNameIndividual],
                'kepala_industri_email' => ['nullable', 'email', 'max:150'],
            ],
            [
                'nama.required' => 'Nama perusahaan wajib diisi.',
                'alamat.required' => 'Alamat perusahaan wajib diisi.',
            ],
            [
                'nama' => 'nama perusahaan',
                'alamat' => 'alamat',
                'email' => 'email',
                'website' => 'website',
                'telp_fax' => 'telepon/fax',
            ],
        )->validate();

        // Saran dihitung sebelum insert supaya record baru tidak menyarankan dirinya sendiri.
        $saran = app(CompanyResolver::class)->suggest($data['nama'], $data['alamat']);

        Company::query()->create([
            ...$data,
            'source' => 'import',
            'created_by' => $this->oleh?->getKey(),
        ]);

        $this->report->sukses($nomor, (string) $data['nama']);

        if ($saran->isNotEmpty()) {
            $this->report->perluTinjau(
                $nomor,
                (string) $data['nama'],
                $saran
                    ->map(fn (array $item): string => $item['perusahaan']->nama.' ('.round($item['skor'] * 100).'%)')
                    ->implode(', '),
            );
        }
    }
}
