<?php

namespace Database\Factories\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payments\Models\PaymentMethod;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement([
                'СБП (Система быстрых платежей)',
                'ЮKassa',
                'Тинькофф Касса',
                'Сбербанк Онлайн',
                'Банковская карта',
                'Наличные при получении',
            ]),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['online', 'offline', 'sbp', 'yookassa', 'tinkoff', 'sberbank', 'qiwi', 'robokassa', 'cloudpayments']),
            'active' => $this->faker->boolean(true),
            'code' => $this->faker->unique()->randomElement(['sbp', 'yookassa', 'tinkoff', 'sberbank', 'card', 'cash', 'qiwi', 'robokassa', 'cloudpayments']),
            'is_russian' => $this->faker->boolean(true),
            'commission' => $this->faker->randomFloat(2, 0, 5),
            'min_amount' => $this->faker->randomFloat(2, 1, 100),
            'max_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'currencies' => ['RUB', 'USD', 'EUR'],
            'settings' => [
                'commission' => $this->faker->randomFloat(2, 0, 5),
                'api_key' => $this->faker->uuid(),
                'shop_id' => $this->faker->uuid(),
            ],
            'test_mode' => $this->faker->boolean(false),
        ];
    }

    /**
     * Фабрика для тестовых данных с нулевой комиссией
     */
    public function zeroCommission(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'commission' => 0,
                'settings' => ['commission' => 0],
            ];
        });
    }

    /**
     * Фабрика для тестовых данных с высокой комиссией
     */
    public function highCommission(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'commission' => 10,
                'settings' => ['commission' => 10],
            ];
        });
    }

    /**
     * Фабрика для тестовых данных с ограничениями по суммам
     */
    public function withLimits(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'min_amount' => 100,
                'max_amount' => 5000,
            ];
        });
    }

    /**
     * Фабрика для неактивного метода оплаты
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
     * Фабрика для российских платежных систем
     */
    public function russian(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_russian' => true,
                'type' => $this->faker->randomElement(['sbp', 'yookassa', 'tinkoff', 'sberbank', 'qiwi', 'robokassa', 'cloudpayments']),
                'code' => $this->faker->randomElement(['sbp', 'yookassa', 'tinkoff', 'sberbank', 'qiwi', 'robokassa', 'cloudpayments']),
            ];
        });
    }

    /**
     * Фабрика для международных платежных систем
     */
    public function international(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_russian' => false,
                'type' => 'online',
                'code' => 'card',
            ];
        });
    }
}
