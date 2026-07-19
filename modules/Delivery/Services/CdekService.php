<?php

namespace Modules\Delivery\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис интеграции с СДЭК API
 */
class CdekService implements DeliveryServiceInterface
{
    private string $account;
    private string $securePassword;
    private ?string $accessToken = null;
    private string $apiUrl = 'https://api.cdek.ru/v2';

    public function __construct(array $settings)
    {
        $this->account = $settings['account'] ?? '';
        $this->securePassword = $settings['secure_password'] ?? '';
    }

    /**
     * Получить токен доступа
     */
    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            $response = Http::asForm()->post("{$this->apiUrl}/oauth/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->account,
                'client_secret' => $this->securePassword,
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json()['access_token'] ?? null;
                return $this->accessToken;
            }
        } catch (\Exception $e) {
            Log::error('CDEK: Ошибка получения токена', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Рассчитать стоимость доставки
     */
    public function calculatePrice(array $params): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['price' => 0, 'days' => 0, 'error' => 'Не удалось получить доступ к API СДЭК'];
        }

        try {
            $from = $params['from'] ?? ['code' => 44]; // Москва по умолчанию
            $to = $params['to'] ?? ['code' => 44];
            $weight = $params['weight'] ?? 1; // кг
            $length = $params['length'] ?? 10; // см
            $width = $params['width'] ?? 10;
            $height = $params['height'] ?? 10;

            $response = Http::withToken($token)->post("{$this->apiUrl}/calculator/tarifflist", [
                'type' => 1, // Доставка
                'currency' => 1, // RUB
                'from_location' => [
                    'code' => is_array($from) ? ($from['code'] ?? 44) : $from,
                ],
                'to_location' => [
                    'code' => is_array($to) ? ($to['code'] ?? 44) : $to,
                ],
                'packages' => [[
                    'weight' => $weight * 1000, // граммы
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                ]],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['tariff_codes'])) {
                    $tariff = $data['tariff_codes'][0] ?? null;
                    if ($tariff) {
                        return [
                            'price' => $tariff['delivery_sum'] ?? 0,
                            'days' => $tariff['period_min'] ?? 0,
                            'error' => null,
                        ];
                    }
                }
            }

            return ['price' => 0, 'days' => 0, 'error' => 'Не удалось рассчитать стоимость'];
        } catch (\Exception $e) {
            Log::error('CDEK: Ошибка расчета стоимости', ['error' => $e->getMessage()]);
            return ['price' => 0, 'days' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Получить список пунктов выдачи
     */
    public function getPickupPoints(string $city, ?string $region = null): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return [];
        }

        try {
            $response = Http::withToken($token)->get("{$this->apiUrl}/deliverypoints", [
                'city' => $city,
                'type' => 'PVZ', // Пункт выдачи
            ]);

            if ($response->successful()) {
                $points = $response->json() ?? [];
                return array_map(function ($point) {
                    return [
                        'code' => $point['code'] ?? '',
                        'name' => $point['name'] ?? '',
                        'address' => $point['location']['address'] ?? '',
                        'work_time' => $point['work_time'] ?? '',
                        'phone' => $point['phone'] ?? '',
                    ];
                }, $points);
            }
        } catch (\Exception $e) {
            Log::error('CDEK: Ошибка получения пунктов выдачи', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Отследить отправление
     */
    public function track(string $trackingNumber): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['error' => 'Не удалось получить доступ к API СДЭК'];
        }

        try {
            $response = Http::withToken($token)->get("{$this->apiUrl}/orders", [
                'cdek_number' => $trackingNumber,
            ]);

            if ($response->successful()) {
                $data = $response->json() ?? [];
                return [
                    'status' => $data['status'] ?? 'unknown',
                    'history' => $data['statuses'] ?? [],
                    'error' => null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('CDEK: Ошибка отслеживания', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }

        return ['error' => 'Отправление не найдено'];
    }

    /**
     * Создать заказ на доставку
     */
    public function createOrder(array $orderData): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return ['error' => 'Не удалось получить доступ к API СДЭК'];
        }

        try {
            $response = Http::withToken($token)->post("{$this->apiUrl}/orders", $orderData);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => $response->json()['error'] ?? 'Ошибка создания заказа'];
        } catch (\Exception $e) {
            Log::error('CDEK: Ошибка создания заказа', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
}





