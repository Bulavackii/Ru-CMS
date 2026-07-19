<?php

namespace Modules\Delivery\Services;

use Modules\Delivery\Models\DeliveryMethod;
use Modules\Delivery\Services\CdekService;
use Modules\Delivery\Services\BoxberryService;
use Modules\Delivery\Services\PochtaService;

/**
 * Сервис для расчета стоимости доставки
 */
class DeliveryCalculatorService
{
    /**
     * Рассчитать стоимость доставки для метода
     *
     * @param DeliveryMethod $method Метод доставки
     * @param array $params Параметры: address, city, region, weight, volume, order_total
     * @return array
     */
    public function calculate(DeliveryMethod $method, array $params): array
    {
        // Проверка на бесплатную доставку при определенной сумме
        if ($method->free_delivery_threshold && isset($params['order_total'])) {
            if ($params['order_total'] >= $method->free_delivery_threshold) {
                return [
                    'price' => 0,
                    'days' => $method->min_days ?? 0,
                    'message' => 'Бесплатная доставка при заказе от ' . number_format($method->free_delivery_threshold, 0, ',', ' ') . ' ₽',
                ];
            }
        }

        // Проверка ограничения по весу
        if ($method->weight_limit && isset($params['weight'])) {
            if ($params['weight'] > $method->weight_limit) {
                return [
                    'price' => 0,
                    'days' => 0,
                    'error' => "Превышен лимит веса. Максимум: {$method->weight_limit} кг",
                ];
            }
        }

        // Проверка доступности в регионе
        if ($method->regions && !empty($method->regions)) {
            $region = $params['region'] ?? $params['city'] ?? '';
            if (!in_array($region, $method->regions) && !in_array('Все регионы РФ', $method->regions)) {
                return [
                    'price' => 0,
                    'days' => 0,
                    'error' => 'Доставка в данный регион недоступна',
                ];
            }
        }

        // Если включена API интеграция, используем соответствующий сервис
        if ($method->api_enabled && $method->api_settings) {
            return $this->calculateViaApi($method, $params);
        }

        // Иначе возвращаем фиксированную цену
        return [
            'price' => $method->price,
            'days' => $method->min_days ?? 0,
            'message' => null,
        ];
    }

    /**
     * Расчет через API службы доставки
     */
    private function calculateViaApi(DeliveryMethod $method, array $params): array
    {
        $service = $this->getService($method);
        if (!$service) {
            return [
                'price' => $method->price,
                'days' => $method->min_days ?? 0,
                'error' => 'Сервис доставки не настроен',
            ];
        }

        $apiParams = [
            'from' => $params['from'] ?? null,
            'to' => $params['to'] ?? $params['city'] ?? null,
            'weight' => $params['weight'] ?? 1,
            'length' => $params['length'] ?? 10,
            'width' => $params['width'] ?? 10,
            'height' => $params['height'] ?? 10,
            'price' => $params['order_total'] ?? 0,
        ];

        $result = $service->calculatePrice($apiParams);

        // Если API вернуло ошибку, используем фиксированную цену как fallback
        if ($result['error']) {
            return [
                'price' => $method->price,
                'days' => $method->min_days ?? 0,
                'error' => $result['error'],
            ];
        }

        return $result;
    }

    /**
     * Получить сервис доставки по коду
     */
    private function getService(DeliveryMethod $method): ?DeliveryServiceInterface
    {
        $code = $method->code;
        $settings = $method->api_settings ?? [];

        switch ($code) {
            case 'cdek':
                return new CdekService($settings);
            case 'boxberry':
                return new BoxberryService($settings);
            case 'pochta':
            case 'pochta_rossii':
                return new PochtaService($settings);
            default:
                return null;
        }
    }

    /**
     * Получить список пунктов выдачи
     */
    public function getPickupPoints(DeliveryMethod $method, string $city, ?string $region = null): array
    {
        if (!$method->api_enabled || !$method->api_settings) {
            return [];
        }

        $service = $this->getService($method);
        if (!$service) {
            return [];
        }

        return $service->getPickupPoints($city, $region);
    }
}

