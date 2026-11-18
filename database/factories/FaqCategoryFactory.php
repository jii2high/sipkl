<?php

namespace Database\Factories;

use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FaqCategory>
 */
class FaqCategoryFactory extends Factory
{
    protected $model = FaqCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(2, true),
            'sort_order' => 0,
        ];
    }
}
