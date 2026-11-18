<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = strtolower(Str::random(10));

        return [
            'nama' => 'PT '.fake()->company(),
            'alamat' => fake()->address(),
            'email' => "info{$slug}@perusahaan.test",
            'website' => "https://{$slug}.perusahaan.test",
            'telp_fax' => '021'.fake()->unique()->numerify('#######'),
            'source' => 'hubin',
        ];
    }
}
