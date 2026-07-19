<?php

namespace Database\Factories\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payments\Models\OrderItem;
use Modules\Payments\Models\Order;
use Modules\News\Models\News;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => $this->faker->numberBetween(1, 100),
            'title' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'qty' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Фабрика для одного товара
     */
    public function single(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty' => 1,
            ];
        });
    }

    /**
     * Фабрика для множественного количества
     */
    public function multiple(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty' => $this->faker->numberBetween(2, 50),
            ];
        });
    }

    /**
     * Фабрика для дорогого товара
     */
    public function expensive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => $this->faker->randomFloat(2, 1000, 10000),
            ];
        });
    }

    /**
     * Фабрика для дешевого товара
     */
    public function cheap(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => $this->faker->randomFloat(2, 1, 10),
            ];
        });
    }

    /**
     * Фабрика для товара с нулевой ценой
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
     * Фабрика для товара с максимальным количеством
     */
    public function maximumQuantity(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty' => 1000,
            ];
        });
    }

    /**
     * Фабрика для товара с минимальным количеством
     */
    public function minimumQuantity(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty' => 1,
            ];
        });
    }

    /**
     * Фабрика для товара с дробным количеством (некорректно)
     */
    public function fractionalQuantity(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty' => 1.5,
            ];
        });
    }

    /**
     * Фабрика для товара с отрицательным количеством (некорректно)
     */
    public function negativeQuantity(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty' => -1,
            ];
        });
    }

    /**
     * Фабрика для товара с нулевым количеством (некорректно)
     */
    public function zeroQuantity(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'qty' => 0,
            ];
        });
    }

    /**
     * Фабрика для товара с очень большой ценой
     */
    public function extremePrice(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => 999999.99,
            ];
        });
    }

    /**
     * Фабрика для товара с очень маленькой ценой
     */
    public function minimalPrice(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'price' => 0.01,
            ];
        });
    }
}
