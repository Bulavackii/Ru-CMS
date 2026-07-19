<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;

/**
 * 💳 Интерфейс платежного гейтвея
 */
interface PaymentGatewayInterface
{
    /**
     * Создать платеж
     */
    public function createPayment(Order $order, array $options = []): array;

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $paymentId): array;

    /**
     * Обработать webhook
     */
    public function handleWebhook(array $data): bool;

    /**
     * Создать возврат
     */
    public function refund(string $paymentId, float $amount, ?string $reason = null): array;

    /**
     * Проверить валидность конфигурации
     */
    public function validateConfig(): bool;
}

