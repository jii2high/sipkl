<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'faq_category_id' => FaqCategory::factory(),
            'pertanyaan' => fake()->sentence().'?',
            'jawaban' => fake()->paragraph(),
            'sort_order' => 0,
            'is_published' => true,
            'created_by' => User::factory(),
        ];
    }
}
