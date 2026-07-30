<?php

namespace Database\Factories;

use App\Models\SuratBalasan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuratBalasan>
 */
class SuratBalasanFactory extends Factory
{
    protected $model = SuratBalasan::class;

    public function definition(): array
    {
        return [
            'file_path' => 'surat-balasan/test.pdf',
        ];
    }
}
