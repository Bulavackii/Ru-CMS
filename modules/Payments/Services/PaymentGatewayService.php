<?php

namespace Modules\Payments\Services;

use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\Models\Order;
use Modules\Payments\Gateways\PaymentGatewayInterface;
use Modules\Payments\Gateways\YooKassaGateway;
use Modules\Payments\Gateways\SBPGateway;
use Modules\Payments\Gateways\TinkoffGateway;
use Modules\Payments\Gateways\CloudPaymentsGateway;
use Modules\Payments\Gateways\RobokassaGateway;
use Modules\Payments\Gateways\QiwiGateway;
use Modules\Payments\Gateways\SberbankGateway;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Сервис для управления платежными гейтвеями
 */
class PaymentGatewayService
{
    /**
     * Получить гейтвей по методу оплаты
     */
    public function getGateway(PaymentMethod $paymentMethod): ?PaymentGatewayInterface
    {
        $code = $paymentMethod->code ?? $paymentMethod->type;

        switch ($code) {
            case 'yookassa':
                return new YooKassaGateway($paymentMethod);
            case 'sbp':
                return new SBPGateway($paymentMethod);
            case 'tinkoff':
                return new TinkoffGateway($paymentMethod);
            case 'cloudpayments':
                return new CloudPaymentsGateway($paymentMethod);
            case 'robokassa':
                return new RobokassaGateway($paymentMethod);
            case 'qiwi':
                return new QiwiGateway($paymentMethod);
            case 'sberbank':
                return new SberbankGateway($paymentMethod);
            default:
                return null;
        }
    }

    /**
     * Создать платеж
     */
    public function createPayment(Order $order, PaymentMethod $paymentMethod, array $options = []): array
    {
        $gateway = $this->getGateway($paymentMethod);

        if (!$gateway) {
            throw new \Exception("Гейтвей для метода оплаты '{$paymentMethod->code}' не найден");
        }

        if (!$gateway->validateConfig()) {
            throw new \Exception("Гейтвей '{$paymentMethod->code}' не настроен правильно");
        }

        return $gateway->createPayment($order, $options);
    }

    /**
     * Обработать webhook
     */
    public function handleWebhook(string $gatewayCode, array $data): bool
    {
        $paymentMethod = PaymentMethod::where('code', $gatewayCode)
            ->orWhere('type', $gatewayCode)
            ->first();

        if (!$paymentMethod) {
            Log::warning("Payment method not found for gateway: {$gatewayCode}");
            return false;
        }

        $gateway = $this->getGateway($paymentMethod);

        if (!$gateway) {
            Log::warning("Gateway not found: {$gatewayCode}");
            return false;
        }

        return $gateway->handleWebhook($data);
    }

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(PaymentMethod $paymentMethod, string $paymentId): array
    {
        $gateway = $this->getGateway($paymentMethod);

        if (!$gateway) {
            return ['success' => false, 'error' => 'Гейтвей не найден'];
        }

        return $gateway->getPaymentStatus($paymentId);
    }

    /**
     * Создать возврат
     */
    public function refund(PaymentMethod $paymentMethod, string $paymentId, float $amount, ?string $reason = null): array
    {
        $gateway = $this->getGateway($paymentMethod);

        if (!$gateway) {
            return ['success' => false, 'error' => 'Гейтвей не найден'];
        }

        return $gateway->refund($paymentId, $amount, $reason);
    }
}

