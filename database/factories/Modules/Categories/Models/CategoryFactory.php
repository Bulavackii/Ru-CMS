<?php

namespace Database\Factories\Modules\Categories\Models;

use Modules\Categories\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Categories\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->optional()->sentence(),
            'type' => $this->faker->optional()->randomElement(['news', 'product', 'page']),
            'icon' => $this->faker->optional()->emoji(),
            'parent_id' => null,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category has a specific type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Indicate that the category is a child of another category.
     */
    public function childOf(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
        ]);
    }
}




