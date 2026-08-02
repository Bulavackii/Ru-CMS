<?php

namespace Modules\Payments\Services;

use Illuminate\Support\Facades\Http;
use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;
use Modules\Payments\Models\PaymentMethod;
use Throwable;

/**
 * Проверка связи с платёжной системой — по образцу шага SMTP в мастере
 * установки: владелец вводит реквизиты и сразу узнаёт, рабочие они или нет,
 * а не выясняет это на первом заказе.
 *
 * ⚠️ Честно о границах. Полноценный приём платежей требует боевого
 * мерчант-аккаунта, согласованного протокола уведомлений и проверки на
 * стороне банка. Здесь проверяется ровно одно: отвечает ли API системы на
 * переданные реквизиты. Там, где драйвера ещё нет, метод так и говорит —
 * заглушка не выдаётся за рабочую интеграцию.
 */
class GatewayChecker
{
    /** Сколько ждём ответа платёжной системы, секунд. */
    private const TIMEOUT = 10;

    /**
     * @return array{ok: bool, message: string}
     */
    public function check(PaymentMethod $method): array
    {
        $settings = (array) $method->settings;

        if ($method->type === 'offline') {
            return $this->result(true, __('admin.payments.check_offline'));
        }

        $required = SeedDefaultPaymentMethodsCommand::credentialFields()[$method->type] ?? [];

        foreach ($required as $field) {
            if (blank($settings[$field] ?? null)) {
                return $this->result(false, __('admin.payments.check_empty'));
            }
        }

        return match ($method->type) {
            'yookassa' => $this->checkYooKassa($settings),
            default => $this->result(false, __('admin.payments.check_no_driver', ['name' => $method->title])),
        };
    }

    /**
     * ЮKassa: список платежей — самый безобидный авторизованный запрос.
     * Он ничего не создаёт и не списывает, но требует валидной пары
     * shopId + секретный ключ, то есть проверяет именно реквизиты.
     */
    private function checkYooKassa(array $settings): array
    {
        try {
            $response = Http::withBasicAuth((string) $settings['shop_id'], (string) $settings['secret_key'])
                ->timeout(self::TIMEOUT)
                ->acceptJson()
                ->get('https://api.yookassa.ru/v3/payments', ['limit' => 1]);

            if ($response->successful()) {
                return $this->result(true, __('admin.payments.check_ok'));
            }

            return $this->result(false, __('admin.payments.check_fail', [
                'error' => 'HTTP ' . $response->status(),
            ]));
        } catch (Throwable $e) {
            return $this->result(false, __('admin.payments.check_fail', ['error' => $e->getMessage()]));
        }
    }

    private function result(bool $ok, string $message): array
    {
        return ['ok' => $ok, 'message' => $message];
    }
}
