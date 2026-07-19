<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Гейтвей CloudPayments
 * 
 * Документация: https://developers.cloudpayments.ru/
 */
class CloudPaymentsGateway extends AbstractPaymentGateway
{
    protected function getGatewayCode(): string
    {
        return 'cloudpayments';
    }

    /**
     * Создать платеж
     */
    public function createPayment(Order $order, array $options = []): array
    {
        $publicId = $this->getConfig('public_id');
        $apiSecret = $this->getConfig('api_secret');

        if (!$publicId || !$apiSecret) {
            throw new \Exception('CloudPayments: не настроены public_id или api_secret');
        }

        $amount = number_format($order->total, 2, '.', '');
        $orderId = (string) $order->id;
        $description = "Заказ #{$order->id}";

        // CloudPayments использует виджет на фронтенде
        // Здесь возвращаем данные для инициализации виджета
        return [
            'success' => true,
            'public_id' => $publicId,
            'amount' => $amount,
            'currency' => 'RUB',
            'invoice_id' => $orderId,
            'description' => $description,
            'account_id' => $order->customer_email ?? $order->customer_phone ?? null,
            'email' => $order->customer_email,
            'widget_type' => 'payment',
            'success_url' => $this->getSuccessUrl($order),
            'fail_url' => $this->getFailUrl($order),
        ];
    }

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $publicId = $this->getConfig('public_id');
        $apiSecret = $this->getConfig('api_secret');

        try {
            $response = Http::withBasicAuth($publicId, $apiSecret)
                ->get("https://api.cloudpayments.ru/payments/find", [
                    'TransactionId' => $paymentId,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $data['Status'] ?? 'unknown',
                    'data' => $data,
                ];
            }

            return ['success' => false, 'error' => 'Не удалось получить статус'];
        } catch (\Exception $e) {
            $this->logError('Get payment status error', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Обработать webhook
     */
    public function handleWebhook(array $data): bool
    {
        $apiSecret = $this->getConfig('api_secret');
        
        // Проверка подписи
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        $signature = base64_encode(hash_hmac('sha256', $content, $apiSecret, true));
        $receivedSignature = $_SERVER['HTTP_CONTENT_HMAC'] ?? '';

        if ($signature !== $receivedSignature) {
            $this->logError('Invalid webhook signature', ['data' => $data]);
            return false;
        }

        $status = $data['Status'] ?? '';
        $orderId = $data['InvoiceId'] ?? null;

        if ($status === 'Completed' || $status === 'Authorized') {
            $order = Order::find($orderId);
            
            if ($order && $order->status !== 'completed') {
                $order->status = 'completed';
                $order->payment_id = $data['TransactionId'] ?? null;
                $order->save();

                $this->log('Payment succeeded', [
                    'order_id' => $orderId,
                    'payment_id' => $data['TransactionId'] ?? null,
                ]);

                return true;
            }
        } elseif ($status === 'Declined' || $status === 'Cancelled') {
            $order = Order::find($orderId);
            
            if ($order) {
                $order->status = 'cancelled';
                $order->save();

                $this->log('Payment canceled', [
                    'order_id' => $orderId,
                    'payment_id' => $data['TransactionId'] ?? null,
                ]);
            }
        }

        return false;
    }

    /**
     * Создать возврат
     */
    public function refund(string $paymentId, float $amount, ?string $reason = null): array
    {
        $publicId = $this->getConfig('public_id');
        $apiSecret = $this->getConfig('api_secret');

        $refundAmount = number_format($amount, 2, '.', '');

        try {
            $response = Http::withBasicAuth($publicId, $apiSecret)
                ->post('https://api.cloudpayments.ru/payments/refund', [
                    'TransactionId' => $paymentId,
                    'Amount' => $refundAmount,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['Success'] ?? false) {
                    return [
                        'success' => true,
                        'refund_id' => $result['TransactionId'] ?? null,
                    ];
                }
            }

            return ['success' => false, 'error' => 'Ошибка возврата'];
        } catch (\Exception $e) {
            $this->logError('Refund error', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Проверить валидность конфигурации
     */
    public function validateConfig(): bool
    {
        return !empty($this->getConfig('public_id')) 
            && !empty($this->getConfig('api_secret'));
    }
}





