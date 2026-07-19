<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Гейтвей Сбербанк Онлайн
 * 
 * Документация: https://developer.sberbank.ru/doc/v1/acquiring/webservice-requests
 */
class SberbankGateway extends AbstractPaymentGateway
{
    protected function getGatewayCode(): string
    {
        return 'sberbank';
    }

    /**
     * Создать платеж
     */
    public function createPayment(Order $order, array $options = []): array
    {
        $userName = $this->getConfig('user_name');
        $password = $this->getConfig('password');

        if (!$userName || !$password) {
            throw new \Exception('Сбербанк: не настроены user_name или password');
        }

        $amount = (int)($order->total * 100); // Сумма в копейках
        $orderId = (string) $order->id;
        $description = "Заказ #{$order->id}";

        $baseUrl = $this->isTestMode() 
            ? 'https://3dsec.sberbank.ru/payment/rest'
            : 'https://securepayments.sberbank.ru/payment/rest';

        // Регистрация заказа
        $registerData = [
            'userName' => $userName,
            'password' => $password,
            'orderNumber' => $orderId,
            'amount' => $amount,
            'returnUrl' => $this->getSuccessUrl($order),
            'failUrl' => $this->getFailUrl($order),
            'description' => $description,
        ];

        // Добавляем дополнительные параметры если есть
        if ($order->customer_email) {
            $registerData['email'] = $order->customer_phone;
        }
        if ($order->customer_phone) {
            $registerData['phone'] = $order->customer_phone;
        }

        try {
            $response = Http::post("{$baseUrl}/register.do", $registerData);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['errorCode']) && $data['errorCode'] !== '0') {
                    $errorMessage = $data['errorMessage'] ?? 'Неизвестная ошибка';
                    throw new \Exception('Ошибка создания платежа: ' . $errorMessage);
                }

                $this->log('Payment created', [
                    'order_id' => $order->id,
                    'order_id_sberbank' => $data['orderId'] ?? null,
                ]);

                return [
                    'success' => true,
                    'payment_id' => $data['orderId'] ?? null,
                    'confirmation_url' => $data['formUrl'] ?? null,
                    'status' => 'pending',
                ];
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
     * Получить статус платежа
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $userName = $this->getConfig('user_name');
        $password = $this->getConfig('password');

        $baseUrl = $this->isTestMode() 
            ? 'https://3dsec.sberbank.ru/payment/rest'
            : 'https://securepayments.sberbank.ru/payment/rest';

        try {
            $response = Http::post("{$baseUrl}/getOrderStatus.do", [
                'userName' => $userName,
                'password' => $password,
                'orderId' => $paymentId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $status = 'unknown';
                if (isset($data['orderStatus'])) {
                    // 0 - заказ зарегистрирован, но не оплачен
                    // 1 - предавторизованная сумма захолдирована
                    // 2 - проведена полная авторизация суммы заказа
                    // 3 - авторизация отменена
                    // 4 - по транзакции была проведена операция возврата
                    // 5 - инициирована авторизация через ACS банка-эмитента
                    // 6 - авторизация отклонена
                    switch ($data['orderStatus']) {
                        case 2:
                            $status = 'completed';
                            break;
                        case 3:
                        case 6:
                            $status = 'cancelled';
                            break;
                        default:
                            $status = 'pending';
                    }
                }

                return [
                    'success' => true,
                    'status' => $status,
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
        // Сбербанк обычно использует callback для уведомлений
        $orderId = $data['orderNumber'] ?? null;
        $status = $data['status'] ?? null;

        if ($status == 2) { // Оплачен
            $order = Order::find($orderId);
            
            if ($order && $order->status !== 'completed') {
                $order->status = 'completed';
                $order->payment_id = $data['orderId'] ?? null;
                $order->save();

                $this->log('Payment succeeded', [
                    'order_id' => $orderId,
                    'order_id_sberbank' => $data['orderId'] ?? null,
                ]);

                return true;
            }
        } elseif ($status == 3 || $status == 6) { // Отменен или отклонен
            $order = Order::find($orderId);
            
            if ($order) {
                $order->status = 'cancelled';
                $order->save();

                $this->log('Payment canceled', [
                    'order_id' => $orderId,
                    'order_id_sberbank' => $data['orderId'] ?? null,
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
        $userName = $this->getConfig('user_name');
        $password = $this->getConfig('password');

        $refundAmount = (int)($amount * 100);

        $baseUrl = $this->isTestMode() 
            ? 'https://3dsec.sberbank.ru/payment/rest'
            : 'https://securepayments.sberbank.ru/payment/rest';

        try {
            $response = Http::post("{$baseUrl}/refund.do", [
                'userName' => $userName,
                'password' => $password,
                'orderId' => $paymentId,
                'amount' => $refundAmount,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['errorCode']) && $result['errorCode'] !== '0') {
                    return ['success' => false, 'error' => $result['errorMessage'] ?? 'Ошибка возврата'];
                }

                return [
                    'success' => true,
                    'refund_id' => $result['orderId'] ?? null,
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
        return !empty($this->getConfig('user_name')) 
            && !empty($this->getConfig('password'));
    }
}





