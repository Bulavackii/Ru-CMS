<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Гейтвей Qiwi
 * 
 * Документация: https://developer.qiwi.com/
 */
class QiwiGateway extends AbstractPaymentGateway
{
    protected function getGatewayCode(): string
    {
        return 'qiwi';
    }

    /**
     * Создать платеж
     */
    public function createPayment(Order $order, array $options = []): array
    {
        $publicKey = $this->getConfig('public_key');
        $secretKey = $this->getConfig('secret_key');

        if (!$publicKey || !$secretKey) {
            throw new \Exception('Qiwi: не настроены public_key или secret_key');
        }

        $amount = number_format($order->total, 2, '.', '');
        $orderId = (string) $order->id;
        $description = "Заказ #{$order->id}";

        $baseUrl = $this->isTestMode() 
            ? 'https://api.qiwi.com/partner/bill/v1/bills'
            : 'https://api.qiwi.com/partner/bill/v1/bills';

        $billId = $orderId . '_' . time();

        $paymentData = [
            'amount' => [
                'currency' => 'RUB',
                'value' => $amount,
            ],
            'comment' => $description,
            'expirationDateTime' => now()->addDays(1)->toIso8601String(),
            'customer' => [
                'phone' => $order->customer_phone ?? null,
                'email' => $order->customer_email ?? null,
            ],
            'customFields' => [
                'order_id' => $orderId,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->put("{$baseUrl}/{$billId}", $paymentData);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->log('Payment created', [
                    'order_id' => $order->id,
                    'bill_id' => $billId,
                ]);

                return [
                    'success' => true,
                    'payment_id' => $billId,
                    'confirmation_url' => $data['payUrl'] ?? null,
                    'status' => $data['status']['value'] ?? 'pending',
                ];
            } else {
                $error = $response->json();
                throw new \Exception('Ошибка создания платежа: ' . ($error['errorMessage'] ?? $response->body()));
            }
        } catch (\Exception $e) {
            $this->logError('Payment creation error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $secretKey = $this->getConfig('secret_key');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
            ])->get("https://api.qiwi.com/partner/bill/v1/bills/{$paymentId}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $data['status']['value'] ?? 'unknown',
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
        $secretKey = $this->getConfig('secret_key');
        
        // Проверка подписи
        $signature = $_SERVER['HTTP_X_API_SIGNATURE_SHA256'] ?? '';
        $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        $expectedSignature = base64_encode(hash_hmac('sha256', $body, $secretKey, true));

        if ($signature !== $expectedSignature) {
            $this->logError('Invalid webhook signature', ['data' => $data]);
            return false;
        }

        $bill = $data['bill'] ?? [];
        $status = $bill['status']['value'] ?? '';
        $orderId = $bill['customFields']['order_id'] ?? null;

        if ($status === 'PAID') {
            $order = Order::find($orderId);
            
            if ($order && $order->status !== 'completed') {
                $order->status = 'completed';
                $order->payment_id = $bill['billId'] ?? null;
                $order->save();

                $this->log('Payment succeeded', [
                    'order_id' => $orderId,
                    'bill_id' => $bill['billId'] ?? null,
                ]);

                return true;
            }
        } elseif ($status === 'REJECTED' || $status === 'EXPIRED') {
            $order = Order::find($orderId);
            
            if ($order) {
                $order->status = 'cancelled';
                $order->save();

                $this->log('Payment canceled', [
                    'order_id' => $orderId,
                    'bill_id' => $bill['billId'] ?? null,
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
        $secretKey = $this->getConfig('secret_key');

        $refundAmount = number_format($amount, 2, '.', '');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post("https://api.qiwi.com/partner/bill/v1/bills/{$paymentId}/refund", [
                'amount' => [
                    'currency' => 'RUB',
                    'value' => $refundAmount,
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'refund_id' => $result['refundId'] ?? null,
                ];
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
        return !empty($this->getConfig('public_key')) 
            && !empty($this->getConfig('secret_key'));
    }
}





