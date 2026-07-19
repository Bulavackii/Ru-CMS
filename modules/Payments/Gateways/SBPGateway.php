<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 💳 Гейтвей СБП (Система быстрых платежей)
 * 
 * Генерация QR-кодов для оплаты через СБП
 */
class SBPGateway extends AbstractPaymentGateway
{
    protected function getGatewayCode(): string
    {
        return 'sbp';
    }

    /**
     * Создать платеж (QR-код)
     */
    public function createPayment(Order $order, array $options = []): array
    {
        $merchantId = $this->getConfig('merchant_id');
        $secretKey = $this->getConfig('secret_key');

        if (!$merchantId || !$secretKey) {
            throw new \Exception('СБП: не настроены merchant_id или secret_key');
        }

        $amount = $order->total;
        $orderId = (string) $order->id;
        $description = "Заказ #{$order->id}";

        // Генерация QR-кода для СБП
        // Формат: https://qr.nspk.ru/... или через API банка
        $qrData = $this->generateQRCode($merchantId, $amount, $orderId, $description);

        $this->log('SBP QR code generated', [
            'order_id' => $order->id,
            'amount' => $amount,
        ]);

        return [
            'success' => true,
            'qr_code' => $qrData['qr_code'] ?? null,
            'qr_url' => $qrData['qr_url'] ?? null,
            'payment_id' => $orderId,
            'status' => 'pending',
        ];
    }

    /**
     * Генерация QR-кода для СБП
     * 
     * В реальном проекте нужно использовать API банка для генерации QR
     */
    protected function generateQRCode(string $merchantId, float $amount, string $orderId, string $description): array
    {
        // Упрощенная реализация
        // В продакшене используйте API вашего банка (Сбербанк, Тинькофф и т.д.)
        
        $qrString = sprintf(
            'ST00012|Name=%s|PersonalAcc=%s|Sum=%s|Purpose=%s',
            urlencode('Оплата заказа'),
            $merchantId,
            number_format($amount * 100, 0, '', ''), // Сумма в копейках
            urlencode($description)
        );

        // Генерация QR-кода через библиотеку (например, SimpleSoftwareIO/simple-qrcode)
        $qrCode = base64_encode($qrString); // Упрощенная версия

        return [
            'qr_code' => $qrCode,
            'qr_url' => route('payments.sbp.qr', ['order' => $orderId]),
            'qr_string' => $qrString,
        ];
    }

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $paymentId): array
    {
        // В реальном проекте нужно проверять статус через API банка
        // Здесь упрощенная версия
        
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
     * Обработать webhook
     */
    public function handleWebhook(array $data): bool
    {
        $status = $data['status'] ?? null;
        $orderId = $data['order_id'] ?? $data['orderId'] ?? null;

        if ($status === 'success' || $status === 'paid' || $status === 'COMPLETED') {
            $order = Order::find($orderId);
            
            if ($order && $order->status !== 'completed') {
                $order->status = 'completed';
                $order->save();

                $this->log('SBP payment succeeded', [
                    'order_id' => $orderId,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Создать возврат
     */
    public function refund(string $paymentId, float $amount, ?string $reason = null): array
    {
        // В реальном проекте нужно использовать API банка для возврата
        $this->log('SBP refund requested', [
            'payment_id' => $paymentId,
            'amount' => $amount,
        ]);

        return [
            'success' => true,
            'message' => 'Запрос на возврат создан',
        ];
    }

    /**
     * Проверить валидность конфигурации
     */
    public function validateConfig(): bool
    {
        return !empty($this->getConfig('merchant_id'));
    }
}

