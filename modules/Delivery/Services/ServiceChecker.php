<?php

namespace Modules\Delivery\Services;

use Modules\Delivery\Console\Commands\SeedDefaultDeliveryMethodsCommand;
use Modules\Delivery\Models\DeliveryMethod;
use Throwable;

/**
 * Проверка связи со службой доставки.
 *
 * ⚠️ Честно о границах: полноценный расчёт требует договора со службой и
 * боевых ключей. Здесь проверяется одно — отвечает ли API службы на
 * переданные реквизиты. Где драйвера ещё нет, метод так и говорит:
 * заглушка не выдаётся за рабочую интеграцию.
 */
class ServiceChecker
{
    public function __construct(private DeliveryCalculatorService $calculator)
    {
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function check(DeliveryMethod $method): array
    {
        $required = SeedDefaultDeliveryMethodsCommand::credentialFields()[$method->code] ?? [];

        if ($required === []) {
            return ['ok' => true, 'message' => __('admin.delivery.check_offline')];
        }

        $settings = (array) $method->api_settings;

        foreach ($required as $field) {
            if (blank($settings[$field] ?? null)) {
                return ['ok' => false, 'message' => __('admin.delivery.check_empty')];
            }
        }

        $service = $this->resolve($method);

        if (! $service) {
            return ['ok' => false, 'message' => __('admin.delivery.check_no_driver', ['name' => $method->title])];
        }

        try {
            // Пробный расчёт минимальной посылки: он ничего не создаёт и
            // не резервирует, но требует валидных ключей.
            $result = $service->calculatePrice([
                'weight' => 1,
                'from_city' => 'Москва',
                'to_city' => 'Санкт-Петербург',
            ]);

            if (($result['success'] ?? false) === true) {
                return ['ok' => true, 'message' => __('admin.delivery.check_ok')];
            }

            return ['ok' => false, 'message' => __('admin.delivery.check_fail', [
                'error' => $result['error'] ?? 'unknown',
            ])];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => __('admin.delivery.check_fail', ['error' => $e->getMessage()])];
        }
    }

    private function resolve(DeliveryMethod $method): ?DeliveryServiceInterface
    {
        $reflection = new \ReflectionMethod($this->calculator, 'getService');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->calculator, $method);
    }
}
