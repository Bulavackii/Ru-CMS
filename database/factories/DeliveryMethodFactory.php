<?php

namespace Database\Factories\Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Delivery\Models\DeliveryMethod;

class DeliveryMethodFactory extends Factory
{
    protected $model = DeliveryMethod::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement([
                'Почта России',
                'СДЭК',
                'ПЭК',
                'Boxberry',
                'Курьером по Москве',
                'Курьером по Санкт-Петербургу',
                'Самовывоз',
            ]),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 0, 1000),
            'active' => $this->faker->boolean(true),
            'code' => $this->faker->unique()->randomElement(['pochta', 'cdek', 'pek', 'boxberry', 'courier_msk', 'courier_spb', 'pickup']),
            'is_russian' => $this->faker->boolean(true),
            'api_enabled' => $this->faker->boolean(false),
            'api_settings' => [],
            'type' => $this->faker->randomElement(['courier', 'pickup', 'post', 'terminal']),
            'min_days' => $this->faker->numberBetween(0, 5),
            'max_days' => $this->faker->numberBetween(5, 14),
            'weight_limit' => $this->faker->randomFloat(2, 5, 100),
            'regions' => ['Все регионы РФ', 'Москва', 'Санкт-Петербург'],
        ];
    }

    /**
     * Фабрика для бесплатной доставки
     */
    public function free(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => 0,
            ];
        });
    }

    /**
     * Фабрика для дорогой доставки
     */
    public function expensive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => $this->faker->randomFloat(2, 1000, 5000),
            ];
        });
    }

    /**
     * Фабрика для неактивной доставки
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'active' => false,
            ];
        });
    }

    /**
     * Фабрика для доставки с API интеграцией
     */
    public function withApi(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'api_enabled' => true,
                'api_settings' => [
                    'api_key' => $this->faker->uuid(),
                    'api_login' => $this->faker->userName(),
                    'calculate_delivery' => true,
                    'track_number' => true,
                ],
            ];
        });
    }

    /**
     * Фабрика для курьерской доставки
     */
    public function courier(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'courier',
                'min_days' => 0,
                'max_days' => 2,
            ];
        });
    }

    /**
     * Фабрика для самовывоза
     */
    public function pickup(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'pickup',
                'price' => $this->faker->randomFloat(2, 0, 200),
                'min_days' => 0,
                'max_days' => 1,
            ];
        });
    }

    /**
     * Фабрика для почтовой доставки
     */
    public function postal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'post',
                'min_days' => 3,
                'max_days' => 14,
                'weight_limit' => 30,
            ];
        });
    }

    /**
     * Фабрика для российских служб доставки
     */
    public function russian(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_russian' => true,
                'code' => $this->faker->randomElement(['pochta', 'cdek', 'pek', 'boxberry']),
            ];
        });
    }

    /**
     * Фабрика для международных служб доставки
     */
    public function international(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_russian' => false,
                'code' => $this->faker->randomElement(['dhl', 'fedex', 'ups']),
            ];
        });
    }

    /**
     * Фабрика для граничных значений веса
     */
    public function withWeightLimits(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'weight_limit' => $this->faker->randomElement([0.1, 1, 10, 100, 1000]),
            ];
        });
    }

    /**
     * Фабрика для граничных значений сроков
     */
    public function withExtremeDays(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'min_days' => $this->faker->randomElement([0, 1, 7]),
                'max_days' => $this->faker->randomElement([1, 14, 30]),
            ];
        });
    }
}
