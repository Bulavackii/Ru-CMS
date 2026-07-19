<?php

namespace Modules\Delivery\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис интеграции с Boxberry API
 */
class BoxberryService implements DeliveryServiceInterface
{
    private string $token;
    private string $apiUrl = 'https://api.boxberry.ru/json.php';

    public function __construct(array $settings)
    {
        $this->token = $settings['token'] ?? '';
    }

    /**
     * Рассчитать стоимость доставки
     */
    public function calculatePrice(array $params): array
    {
        try {
            $target = $params['to'] ?? '';
            $weight = $params['weight'] ?? 1;
            $price = $params['price'] ?? 0;

            $response = Http::get($this->apiUrl, [
                'token' => $this->token,
                'method' => 'DeliveryCosts',
                'target' => $target,
                'weight' => $weight,
                'ordersum' => $price,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['price'])) {
                    return [
                        'price' => (float) $data['price'],
                        'days' => (int) ($data['delivery_period'] ?? 0),
                        'error' => null,
                    ];
                }
            }

            return ['price' => 0, 'days' => 0, 'error' => 'Не удалось рассчитать стоимость'];
        } catch (\Exception $e) {
            Log::error('Boxberry: Ошибка расчета стоимости', ['error' => $e->getMessage()]);
            return ['price' => 0, 'days' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получить список пунктов выдачи
     */
    public function getPickupPoints(string $city, ?string $region = null): array
    {
        try {
            $response = Http::get($this->apiUrl, [
                'token' => $this->token,
                'method' => 'ListPoints',
                'CityName' => $city,
            ]);

            if ($response->successful()) {
                $points = $response->json() ?? [];
                return array_map(function ($point) {
                    return [
                        'code' => $point['Code'] ?? '',
                        'name' => $point['Name'] ?? '',
                        'address' => $point['Address'] ?? '',
                        'work_time' => $point['WorkShedule'] ?? '',
                        'phone' => $point['Phone'] ?? '',
                    ];
                }, $points);
            }
        } catch (\Exception $e) {
            Log::error('Boxberry: Ошибка получения пунктов выдачи', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Отследить отправление
     */
    public function track(string $trackingNumber): array
    {
        try {
            $response = Http::get($this->apiUrl, [
                'token' => $this->token,
                'method' => 'ParselStory',
                'ImId' => $trackingNumber,
            ]);

            if ($response->successful()) {
                $data = $response->json() ?? [];
                return [
                    'status' => $data['Status'] ?? 'unknown',
                    'history' => $data['history'] ?? [],
                    'error' => null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Boxberry: Ошибка отслеживания', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }

        return ['error' => 'Отправление не найдено'];
    }

    /**
     * Создать заказ на доставку
     */
    public function createOrder(array $orderData): array
    {
        try {
            $response = Http::post($this->apiUrl, array_merge([
                'token' => $this->token,
                'method' => 'ParselCreate',
            ], $orderData));

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json()['err'] ?? 'Ошибка создания заказа'];
        } catch (\Exception $e) {
            Log::error('Boxberry: Ошибка создания заказа', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
}





