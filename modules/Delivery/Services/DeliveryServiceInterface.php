<?php

namespace Modules\Delivery\Services;

/**
 * Интерфейс для сервисов доставки
 */
interface DeliveryServiceInterface
{
    /**
     * Рассчитать стоимость доставки
     *
     * @param array $params Параметры: from, to, weight, volume, etc.
     * @return array ['price' => float, 'days' => int, 'error' => string|null]
     */
    public function calculatePrice(array $params): array;

    /**
     * Получить список пунктов выдачи
     *
     * @param string $city Город
     * @param string|null $region Регион
     * @return array
     */
    public function getPickupPoints(string $city, ?string $region = null): array;

    /**
     * Отследить отправление
     *
     * @param string $trackingNumber Трек-номер
     * @return array
     */
    public function track(string $trackingNumber): array;

    /**
     * Создать заказ на доставку
     *
     * @param array $orderData Данные заказа
     * @return array
     */
    public function createOrder(array $orderData): array;
}





