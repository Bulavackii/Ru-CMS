<?php

namespace Database\Factories\Modules\News\Models;

use Modules\News\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'slug' => fake()->unique()->slug(),
            'meta_title' => fake()->sentence(),
            'meta_description' => fake()->text(160),
            'published' => fake()->boolean(80),
            'template' => fake()->randomElement(['default', 'news', 'products']),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published' => false,
        ]);
    }
}

