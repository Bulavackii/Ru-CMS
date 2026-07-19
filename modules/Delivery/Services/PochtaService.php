<?php

namespace Modules\Delivery\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис интеграции с Почтой России API
 */
class PochtaService implements DeliveryServiceInterface
{
    private string $login;
    private string $password;
    private string $apiUrl = 'https://otpravka-api.pochta.ru';

    public function __construct(array $settings)
    {
        $this->login = $settings['login'] ?? '';
        $this->password = $settings['password'] ?? '';
    }

    /**
     * Рассчитать стоимость доставки
     */
    public function calculatePrice(array $params): array
    {
        try {
            $from = $params['from'] ?? '101000'; // Москва по умолчанию
            $to = $params['to'] ?? '101000';
            $weight = $params['weight'] ?? 1000; // граммы
            $mailType = $params['mail_type'] ?? 'POSTAL_PARCEL'; // Посылка

            $response = Http::withBasicAuth($this->login, $this->password)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->apiUrl}/1.0/tariff", [
                    'object' => $mailType,
                    'from' => is_array($from) ? ($from['index'] ?? $from) : $from,
                    'to' => is_array($to) ? ($to['index'] ?? $to) : $to,
                    'weight' => $weight,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'price' => (float) ($data['total'] ?? 0),
                    'days' => (int) ($data['delivery_time']['max'] ?? 0),
                    'error' => null,
                ];
            }

            return ['price' => 0, 'days' => 0, 'error' => 'Не удалось рассчитать стоимость'];
        } catch (\Exception $e) {
            Log::error('Почта России: Ошибка расчета стоимости', ['error' => $e->getMessage()]);
            return ['price' => 0, 'days' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получить список пунктов выдачи (почтовые отделения)
     */
    public function getPickupPoints(string $city, ?string $region = null): array
    {
        try {
            $response = Http::withBasicAuth($this->login, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get("{$this->apiUrl}/1.0/postoffice", [
                    'filter' => $city,
                ]);

            if ($response->successful()) {
                $points = $response->json() ?? [];
                return array_map(function ($point) {
                    return [
                        'code' => $point['postal_code'] ?? '',
                        'name' => $point['name'] ?? '',
                        'address' => $point['address'] ?? '',
                        'work_time' => $point['working_hours'] ?? '',
                        'phone' => $point['phone'] ?? '',
                    ];
                }, $points);
            }
        } catch (\Exception $e) {
            Log::error('Почта России: Ошибка получения отделений', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Отследить отправление
     */
    public function track(string $trackingNumber): array
    {
        try {
            $response = Http::get('https://www.pochta.ru/tracking', [
                'p' => $trackingNumber,
            ]);

            // Парсинг HTML ответа (упрощенный вариант)
            // В реальном проекте лучше использовать официальное API
            return [
                'status' => 'in_transit',
                'history' => [],
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Почта России: Ошибка отслеживания', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Создать заказ на доставку
     */
    public function createOrder(array $orderData): array
    {
        try {
            $response = Http::withBasicAuth($this->login, $this->password)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->apiUrl}/1.0/user/shipment", $orderData);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json()['error'] ?? 'Ошибка создания заказа'];
        } catch (\Exception $e) {
            Log::error('Почта России: Ошибка создания заказа', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
}





