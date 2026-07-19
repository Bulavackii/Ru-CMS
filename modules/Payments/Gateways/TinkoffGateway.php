<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Гейтвей Тинькофф Банк
 * 
 * Документация: https://www.tinkoff.ru/kassa/develop/api/
 */
class TinkoffGateway extends AbstractPaymentGateway
{
    protected function getGatewayCode(): string
    {
        return 'tinkoff';
    }

    /**
     * Создать платеж
     */
    public function createPayment(Order $order, array $options = []): array
    {
        $terminalKey = $this->getConfig('terminal_key');
        $secretKey = $this->getConfig('secret_key');

        if (!$terminalKey || !$secretKey) {
            throw new \Exception('Тинькофф: не настроены terminal_key или secret_key');
        }

        $baseUrl = $this->isTestMode() 
            ? 'https://rest-api-test.tinkoff.ru/v2/Init' 
            : 'https://securepay.tinkoff.ru/v2/Init';

        $amount = (int)($order->total * 100); // Сумма в копейках
        $orderId = (string) $order->id;
        $description = "Заказ #{$order->id}";

        $paymentData = [
            'TerminalKey' => $terminalKey,
            'Amount' => $amount,
            'OrderId' => $orderId,
            'Description' => $description,
            'SuccessURL' => $this->getSuccessUrl($order),
            'FailURL' => $this->getFailUrl($order),
            'NotificationURL' => $this->getWebhookUrl(),
            'Receipt' => [
                'Email' => $order->customer_email ?? '',
                'Taxation' => 'usn_income',
                'Items' => [
                    [
                        'Name' => $description,
                        'Price' => $amount,
                        'Quantity' => 1,
                        'Amount' => $amount,
                        'Tax' => 'none',
                    ],
                ],
            ],
        ];

        // Подпись запроса
        $paymentData['Token'] = $this->generateToken($paymentData, $secretKey);

        try {
            $response = Http::post($baseUrl, $paymentData);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['Success'] ?? false) {
                    $this->log('Payment created', [
                        'order_id' => $order->id,
                        'payment_id' => $data['PaymentId'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'payment_id' => $data['PaymentId'] ?? null,
                        'confirmation_url' => $data['PaymentURL'] ?? null,
                        'status' => 'pending',
                    ];
                } else {
                    $error = $data['Message'] ?? 'Неизвестная ошибка';
                    throw new \Exception('Ошибка создания платежа: ' . $error);
                }
            } else {
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
     * Генерация токена для подписи запроса
     */
    private function generateToken(array $data, string $secretKey): string
    {
        // Удаляем Token и Receipt из данных для подписи
        $signData = $data;
        unset($signData['Token'], $signData['Receipt']);
        
        // Сортируем по ключам
        ksort($signData);
        
        // Формируем строку для подписи
        $signString = '';
        foreach ($signData as $key => $value) {
            if (is_array($value)) {
                $signString .= $key . ':' . json_encode($value, JSON_UNESCAPED_UNICODE) . ';';
            } else {
                $signString .= $key . ':' . $value . ';';
            }
        }
        
        $signString .= $secretKey;
        
        return hash('sha256', $signString);
    }

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $terminalKey = $this->getConfig('terminal_key');
        $secretKey = $this->getConfig('secret_key');

        $data = [
            'TerminalKey' => $terminalKey,
            'PaymentId' => $paymentId,
        ];

        $data['Token'] = $this->generateToken($data, $secretKey);

        try {
            $response = Http::post('https://securepay.tinkoff.ru/v2/GetState', $data);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => $result['Success'] ?? false,
                    'status' => $result['Status'] ?? 'unknown',
                    'data' => $result,
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
        $token = $data['Token'] ?? '';

        // Проверка подписи
        $checkData = $data;
        unset($checkData['Token']);
        $expectedToken = $this->generateToken($checkData, $secretKey);

        if ($token !== $expectedToken) {
            $this->logError('Invalid webhook signature', ['data' => $data]);
            return false;
        }

        $status = $data['Status'] ?? '';
        $orderId = $data['OrderId'] ?? null;

        if ($status === 'CONFIRMED' || $status === 'AUTHORIZED') {
            $order = Order::find($orderId);
            
            if ($order && $order->status !== 'completed') {
                $order->status = 'completed';
                $order->payment_id = $data['PaymentId'] ?? null;
                $order->save();

                $this->log('Payment succeeded', [
                    'order_id' => $orderId,
                    'payment_id' => $data['PaymentId'] ?? null,
                ]);

                return true;
            }
        } elseif ($status === 'REJECTED' || $status === 'CANCELED') {
            $order = Order::find($orderId);
            
            if ($order) {
                $order->status = 'cancelled';
                $order->save();

                $this->log('Payment canceled', [
                    'order_id' => $orderId,
                    'payment_id' => $data['PaymentId'] ?? null,
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
        $terminalKey = $this->getConfig('terminal_key');
        $secretKey = $this->getConfig('secret_key');

        $refundAmount = (int)($amount * 100);

        $data = [
            'TerminalKey' => $terminalKey,
            'PaymentId' => $paymentId,
            'Amount' => $refundAmount,
        ];

        if ($reason) {
            $data['Description'] = $reason;
        }

        $data['Token'] = $this->generateToken($data, $secretKey);

        try {
            $response = Http::post('https://securepay.tinkoff.ru/v2/Cancel', $data);

            if ($response->successful()) {
                $result = $response->json();
                if ($result['Success'] ?? false) {
                    return [
                        'success' => true,
                        'refund_id' => $result['PaymentId'] ?? null,
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
        return !empty($this->getConfig('terminal_key')) 
            && !empty($this->getConfig('secret_key'));
    }
}





