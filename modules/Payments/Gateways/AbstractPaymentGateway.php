<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Абстрактный класс платежного гейтвея
 */
abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    protected PaymentMethod $paymentMethod;
    protected array $config;

    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->config = $paymentMethod->settings ?? [];
    }

    /**
     * Получить значение настройки
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Проверить, включен ли тестовый режим
     */
    protected function isTestMode(): bool
    {
        return $this->paymentMethod->test_mode ?? false;
    }

    /**
     * Получить URL для редиректа после успешной оплаты
     */
    protected function getSuccessUrl(Order $order): string
    {
        return $this->getConfig('success_url') 
            ?? route('payments.success', ['order' => $order->id]);
    }

    /**
     * Получить URL для редиректа после неудачной оплаты
     */
    protected function getFailUrl(Order $order): string
    {
        return $this->getConfig('fail_url') 
            ?? route('payments.fail', ['order' => $order->id]);
    }

    /**
     * Получить URL для webhook
     */
    protected function getWebhookUrl(): string
    {
        return $this->getConfig('webhook_url') 
            ?? route('payments.webhook', ['gateway' => $this->getGatewayCode()]);
    }

    /**
     * Получить код гейтвея
     */
    abstract protected function getGatewayCode(): string;

    /**
     * Логирование
     */
    protected function log(string $message, array $context = []): void
    {
        Log::info("[{$this->getGatewayCode()}] {$message}", $context);
    }

    /**
     * Логирование ошибок
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->getGatewayCode()}] {$message}", $context);
    }
}

