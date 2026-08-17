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
     * @param array $params Параметры: address, city, region, weight, volume,
     *                      order_total, skip_api
     * @return array
     *
     * ⚠️ `skip_api => true` — для оформления заказа.
     *
     * Расчёт через API службы уходит наружу с таймаутом 15–20 секунд. В
     * корзине это означает, что покупатель полминуты смотрит на замершую
     * кнопку, а недоступная служба и вовсе рвёт оформление. Правила
     * (вес, регион, порог бесплатной доставки) проверяются ДО этой ветки и
     * работают всегда — наружу не ходит только цена, вместо неё берётся
     * фиксированная.
     */
    public function calculate(DeliveryMethod $method, array $params): array
    {
        // Проверка на бесплатную доставку при определенной сумме
        if ($method->free_delivery_threshold && isset($params['order_total'])) {
            if ($params['order_total'] >= $method->free_delivery_threshold) {
                return [
                    'price' => 0,
                    'days' => $method->min_days ?? 0,
                    'message' => __('frontend.cart.free_delivery_from', ['sum' => number_format($method->free_delivery_threshold, 0, ',', ' ')]),
                ];
            }
        }

        // Проверка ограничения по весу.
        //
        // ⚠️ Вес сравнивается, только если он ИЗВЕСТЕН. Пустой вес значит «не
        // взвешиваем» (заказ из услуг), а не ноль: иначе такой заказ формально
        // проходил бы любой лимит и создавал ложное чувство проверки.
        if ($method->weight_limit && isset($params['weight']) && $params['weight'] !== null) {
            if ((float) $params['weight'] > (float) $method->weight_limit) {
                return [
                    'price' => 0,
                    'days' => 0,
                    'error' => __('frontend.cart.weight_exceeded', ['limit' => (float) $method->weight_limit]),
                ];
            }
        }

        // Проверка доступности в регионе. Сверку ведёт САМА МОДЕЛЬ
        // (`isAvailableInRegion`) — второй список условий здесь рано или поздно
        // разошёлся бы с тем, что показывает панель.
        if (! empty($method->regions)) {
            $region = $params['region'] ?? $params['city'] ?? '';

            if (! $method->isAvailableInRegion($region)) {
                return [
                    'price' => 0,
                    'days' => 0,
                    'error' => __('frontend.cart.region_unavailable'),
                ];
            }
        }

        // Если включена API интеграция, используем соответствующий сервис.
        // При оформлении заказа наружу не ходим — см. пояснение к методу.
        if ($method->api_enabled && $method->api_settings && empty($params['skip_api'])) {
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

        // Если API вернуло ошибку, используем фиксированную цену как fallback.
        // ⚠️ Ключ читаем через `??`: свой драйвер службы может его не положить,
        // и прямое обращение дало бы «Undefined array key» — то есть 500 при
        // расчёте доставки вместо запасной фиксированной цены.
        if (! empty($result['error'])) {
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

