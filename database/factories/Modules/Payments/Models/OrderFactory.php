<?php

namespace Database\Factories\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Modules\Delivery\Models\DeliveryMethod;
use App\Models\User;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'payment_method_id' => PaymentMethod::factory(),
            'delivery_method_id' => DeliveryMethod::factory(),
            'total' => $this->faker->randomFloat(2, 100, 10000),
            'items_total' => $this->faker->randomFloat(2, 50, 8000),
            'delivery_price' => $this->faker->randomFloat(2, 0, 500),
            'commission' => $this->faker->randomFloat(2, 0, 200),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'is_new' => $this->faker->boolean(true),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_email' => $this->faker->email(),
            'customer_address' => $this->faker->address(),
            'comment' => $this->faker->sentence(),
        ];
    }

    /**
     * Фабрика для нового заказа в ожидании
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'is_new' => true,
            ];
        });
    }

    /**
     * Фабрика для обрабатываемого заказа
     */
    public function processing(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
                'is_new' => false,
            ];
        });
    }

    /**
     * Фабрика для завершенного заказа
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'is_new' => false,
            ];
        });
    }

    /**
     * Фабрика для отмененного заказа
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'is_new' => false,
            ];
        });
    }

    /**
     * Фабрика для заказа с пользователем
     */
    public function withUser(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'user_id' => User::factory(),
            ];
        });
    }

    /**
     * Фабрика для заказа без доставки
     */
    public function withoutDelivery(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'delivery_method_id' => null,
                'delivery_price' => 0,
            ];
        });
    }

    /**
     * Фабрика для заказа с высокой комиссией
     */
    public function withHighCommission(): static
    {
        return $this->state(function (array $attributes) {
            $itemsTotal = $this->faker->randomFloat(2, 1000, 5000);
            $commission = $itemsTotal * 0.1; // 10% комиссия
            return [
                'items_total' => $itemsTotal,
                'commission' => $commission,
                'total' => $itemsTotal + $attributes['delivery_price'] + $commission,
            ];
        });
    }

    /**
     * Фабрика для заказа с нулевой комиссией
     */
    public function withZeroCommission(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'commission' => 0,
            ];
        });
    }

    /**
     * Фабрика для заказа с разными статусами
     */
    public function withStatus(string $status): static
    {
        return $this->state(function (array $attributes) use ($status) {
            return [
                'status' => $status,
                'is_new' => $status === 'pending',
            ];
        });
    }

    /**
     * Фабрика для заказа с минимальной суммой
     */
    public function withMinimumAmount(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'items_total' => 1.00,
                'total' => 1.00 + ($attributes['delivery_price'] ?? 0) + ($attributes['commission'] ?? 0),
            ];
        });
    }

    /**
     * Фабрика для заказа с максимальной суммой
     */
    public function withMaximumAmount(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'items_total' => 1000000.00,
                'total' => 1000000.00 + ($attributes['delivery_price'] ?? 0) + ($attributes['commission'] ?? 0),
            ];
        });
    }

    /**
     * Фабрика для заказа с пустыми данными клиента
     */
    public function withEmptyCustomerData(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'customer_name' => null,
                'customer_phone' => null,
                'customer_email' => null,
                'customer_address' => null,
            ];
        });
    }

    /**
     * Фабрика для заказа с невалидными данными
     */
    public function withInvalidData(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'customer_email' => 'invalid-email',
                'customer_phone' => 'not-a-phone',
                'total' => -100,
            ];
        });
    }
}
