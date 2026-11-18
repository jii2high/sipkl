<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'nama' => 'PT '.fake()->unique()->company(),
            'alamat' => fake()->address(),
            'email' => fake()->unique()->companyEmail(),
            'website' => fake()->unique()->domainName(),
            'telp_fax' => fake()->unique()->numerify('021#######'),
            'cp_name' => fake()->name(),
            'cp_phone' => fake()->numerify('08##########'),
            'pembimbing_industri_nama' => null,
            'pembimbing_industri_email' => null,
            'pembimbing_industri_email_verified_at' => null,
            'kepala_industri_nama' => null,
            'kepala_industri_email' => null,
            'kepala_industri_email_verified_at' => null,
            'source' => 'hubin',
            'created_by' => null,
            'merged_into_id' => null,
        ];
    }

    public function dariSiswa(): static
    {
        return $this->state(fn () => ['source' => 'siswa']);
    }

    public function dariImpor(): static
    {
        return $this->state(fn () => ['source' => 'import']);
    }

    public function dibuatOleh(User $user): static
    {
        return $this->state(fn () => ['created_by' => $user->id]);
    }

    /**
     * Perusahaan yang sudah digabung ke perusahaan lain (tidak lagi jadi kandidat).
     */
    public function sudahMergeKe(Company $target): static
    {
        return $this->state(fn () => ['merged_into_id' => $target->id]);
    }

    /**
     * Signer Pembimbing Industri yang sudah terverifikasi emailnya.
     */
    public function signerPembimbingTerverifikasi(): static
    {
        return $this->state(fn () => [
            'pembimbing_industri_nama' => fake()->name(),
            'pembimbing_industri_email' => fake()->unique()->companyEmail(),
            'pembimbing_industri_email_verified_at' => now(),
        ]);
    }
}
