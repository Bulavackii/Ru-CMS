<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Гейтвей ЮKassa (Яндекс.Касса)
 * 
 * Документация: https://yookassa.ru/developers/api
 */
class YooKassaGateway extends AbstractPaymentGateway
{
    protected function getGatewayCode(): string
    {
        return 'yookassa';
    }

    /**
     * Создать платеж
     */
    public function createPayment(Order $order, array $options = []): array
    {
        $shopId = $this->getConfig('shop_id');
        $secretKey = $this->getConfig('secret_key');

        if (!$shopId || !$secretKey) {
            throw new \Exception('ЮKassa: не настроены shop_id или secret_key');
        }

        $baseUrl = $this->isTestMode() 
            ? 'https://api.yookassa.ru/v3/payments' 
            : 'https://api.yookassa.ru/v3/payments';

        $amount = $order->total;
        $description = "Заказ #{$order->id}";

        $paymentData = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $this->getSuccessUrl($order),
            ],
            'capture' => true,
            'description' => $description,
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
        ];

        try {
            $response = Http::withBasicAuth($shopId, $secretKey)
                ->post($baseUrl, $paymentData);

            if ($response->successful()) {
                $data = $response->json();
                
                $this->log('Payment created', [
                    'order_id' => $order->id,
                    'payment_id' => $data['id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'payment_id' => $data['id'] ?? null,
                    'confirmation_url' => $data['confirmation']['confirmation_url'] ?? null,
                    'status' => $data['status'] ?? 'pending',
                ];
            } else {
                $this->logError('Payment creation failed', [
                    'order_id' => $order->id,
                    'response' => $response->json(),
                ]);

                throw new \Exception('Ошибка создания платежа: ' . $response->body());
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
        $shopId = $this->getConfig('shop_id');
        $secretKey = $this->getConfig('secret_key');

        try {
            $response = Http::withBasicAuth($shopId, $secretKey)
                ->get("https://api.yookassa.ru/v3/payments/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
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
        $event = $data['event'] ?? null;
        $payment = $data['object'] ?? [];

        if ($event === 'payment.succeeded') {
            $orderId = $payment['metadata']['order_id'] ?? null;
            
            if ($orderId) {
                $order = Order::find($orderId);
                if ($order && $order->status !== 'completed') {
                    $order->status = 'completed';
                    $order->save();

                    $this->log('Payment succeeded', [
                        'order_id' => $orderId,
                        'payment_id' => $payment['id'] ?? null,
                    ]);

                    return true;
                }
            }
        } elseif ($event === 'payment.canceled') {
            $orderId = $payment['metadata']['order_id'] ?? null;
            
            if ($orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $order->status = 'cancelled';
                    $order->save();

                    $this->log('Payment canceled', [
                        'order_id' => $orderId,
                        'payment_id' => $payment['id'] ?? null,
                    ]);
                }
            }
        }

        return false;
    }

    /**
     * Создать возврат
     */
    public function refund(string $paymentId, float $amount, ?string $reason = null): array
    {
        $shopId = $this->getConfig('shop_id');
        $secretKey = $this->getConfig('secret_key');

        $refundData = [
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'RUB',
            ],
        ];

        if ($reason) {
            $refundData['description'] = $reason;
        }

        try {
            $response = Http::withBasicAuth($shopId, $secretKey)
                ->post("https://api.yookassa.ru/v3/refunds", $refundData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['id'] ?? null,
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
        return !empty($this->getConfig('shop_id')) 
            && !empty($this->getConfig('secret_key'));
    }
}

