<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Гейтвей Робокасса
 * 
 * Документация: https://docs.robokassa.ru/
 */
class RobokassaGateway extends AbstractPaymentGateway
{
    protected function getGatewayCode(): string
    {
        return 'robokassa';
    }

    /**
     * Создать платеж
     */
    public function createPayment(Order $order, array $options = []): array
    {
        $merchantLogin = $this->getConfig('merchant_login');
        $password1 = $this->getConfig('password_1'); // Пароль #1
        $password2 = $this->getConfig('password_2'); // Пароль #2 (для проверки ResultURL)

        if (!$merchantLogin || !$password1) {
            throw new \Exception('Робокасса: не настроены merchant_login или password_1');
        }

        $amount = number_format($order->total, 2, '.', '');
        $orderId = (string) $order->id;
        $description = "Заказ #{$order->id}";

        $baseUrl = $this->isTestMode() 
            ? 'https://auth.robokassa.ru/Merchant/Index.aspx'
            : 'https://auth.robokassa.ru/Merchant/Index.aspx';

        // Формирование подписи
        $signatureString = "{$merchantLogin}:{$amount}:{$orderId}:{$password1}";
        $signature = md5($signatureString);

        // Параметры для формы оплаты
        $paymentUrl = $baseUrl . '?' . http_build_query([
            'MerchantLogin' => $merchantLogin,
            'OutSum' => $amount,
            'InvId' => $orderId,
            'Description' => $description,
            'SignatureValue' => $signature,
            'Culture' => 'ru',
            'Encoding' => 'utf-8',
            'IsTest' => $this->isTestMode() ? 1 : 0,
        ]);

        $this->log('Payment created', [
            'order_id' => $order->id,
            'payment_url' => $paymentUrl,
        ]);

        return [
            'success' => true,
            'payment_id' => $orderId,
            'confirmation_url' => $paymentUrl,
            'status' => 'pending',
        ];
    }

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $paymentId): array
    {
        // Робокасса не предоставляет API для проверки статуса
        // Статус проверяется через webhook
        $order = Order::find($paymentId);
        
        if (!$order) {
            return ['success' => false, 'error' => 'Заказ не найден'];
        }

        return [
            'success' => true,
            'status' => $order->status,
            'payment_id' => $paymentId,
        ];
    }

    /**
     * Обработать webhook (ResultURL)
     */
    public function handleWebhook(array $data): bool
    {
        $password2 = $this->getConfig('password_2');
        
        if (!$password2) {
            $this->logError('Password #2 not configured');
            return false;
        }

        $outSum = $data['OutSum'] ?? '';
        $invId = $data['InvId'] ?? '';
        $signature = $data['SignatureValue'] ?? '';

        // Проверка подписи
        $signatureString = "{$outSum}:{$invId}:{$password2}";
        $expectedSignature = strtoupper(md5($signatureString));

        if (strtoupper($signature) !== $expectedSignature) {
            $this->logError('Invalid webhook signature', ['data' => $data]);
            return false;
        }

        $order = Order::find($invId);
        
        if ($order && $order->status !== 'completed') {
            $order->status = 'completed';
            $order->payment_id = $invId;
            $order->save();

            $this->log('Payment succeeded', [
                'order_id' => $invId,
                'amount' => $outSum,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Создать возврат
     */
    public function refund(string $paymentId, float $amount, ?string $reason = null): array
    {
        // Робокасса требует ручной обработки возвратов через личный кабинет
        $this->log('Refund requested', [
            'payment_id' => $paymentId,
            'amount' => $amount,
        ]);

        return [
            'success' => true,
            'message' => 'Запрос на возврат создан. Обработайте через личный кабинет Робокассы.',
        ];
    }

    /**
     * Проверить валидность конфигурации
     */
    public function validateConfig(): bool
    {
        return !empty($this->getConfig('merchant_login')) 
            && !empty($this->getConfig('password_1'))
            && !empty($this->getConfig('password_2'));
    }
}





