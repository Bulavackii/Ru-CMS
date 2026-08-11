<?php

namespace Modules\Delivery\Console\Commands;

use Illuminate\Console\Command;
use Modules\Delivery\Models\DeliveryMethod;

/**
 * Типовые для РФ службы доставки, создаваемые при установке CMS.
 *
 * ⚠️ Методы создаются ВЫКЛЮЧЕННЫМИ и БЕЗ ключей API. Боевые токены в коде
 * не хранятся и храниться не должны: репозиторий публичный. Владелец
 * вводит их сам в панели, а docs_url подсказывает, где их выдают.
 *
 * Идемпотентно: повторный запуск не плодит дубли и НЕ перетирает то, что
 * владелец уже настроил — сверка идёт по коду службы.
 */
class SeedDefaultDeliveryMethodsCommand extends Command
{
    protected $signature = 'delivery:seed-default {--reset : Пересоздать методы, потеряв настройки}';

    protected $description = 'Создать типовые для РФ способы доставки (выключенными, без ключей)';

    public function handle(): int
    {
        $created = self::seed((bool) $this->option('reset'));

        $this->info($created > 0
            ? "Добавлено способов доставки: {$created}"
            : 'Все типовые способы доставки уже заведены — ничего не меняли.');

        return self::SUCCESS;
    }

    /**
     * Коды служб — идентификаторы, по ним калькулятор выбирает драйвер
     * (см. DeliveryCalculatorService::getService). Менять нельзя.
     *
     * Ключи api_settings пустые намеренно: это подсказка форме о том,
     * какие реквизиты нужны службе, а не место для боевых токенов.
     */
    /**
     * Службы, включённые сразу после установки. Ключи им не нужны:
     * стоимость фиксированная, расчёт по API выключен.
     */
    public const ENABLED_BY_DEFAULT = ['pochta', 'cdek', 'courier_local'];

    public static function definitions(): array
    {
        return [
            [
                'code' => 'pochta',
                'title' => 'Почта России',
                'description' => 'Доставка в отделение или курьером по всей стране.',
                'type' => 'post',
                'price' => 350,
                'min_days' => 3,
                'max_days' => 14,
                'weight_limit' => 20,
                'docs_url' => 'https://otpravka.pochta.ru/specification',
                'api_settings' => ['token' => '', 'login' => '', 'password' => ''],
            ],
            [
                'code' => 'cdek',
                'title' => 'СДЭК',
                'description' => 'Пункты выдачи и курьер, расчёт по весу и городу.',
                'type' => 'courier',
                'price' => 400,
                'min_days' => 2,
                'max_days' => 7,
                'weight_limit' => 30,
                'docs_url' => 'https://api-docs.cdek.ru/',
                'api_settings' => ['account' => '', 'secure_password' => ''],
            ],
            [
                'code' => 'boxberry',
                'title' => 'Boxberry',
                'description' => 'Выдача в пунктах Boxberry и курьерская доставка.',
                'type' => 'terminal',
                'price' => 320,
                'min_days' => 2,
                'max_days' => 8,
                'weight_limit' => 15,
                'docs_url' => 'https://help.boxberry.ru/',
                'api_settings' => ['token' => ''],
            ],
            [
                'code' => 'yandex_delivery',
                'title' => 'Яндекс Доставка',
                'description' => 'Курьер по городу и доставка в пункты выдачи.',
                'type' => 'courier',
                'price' => 300,
                'min_days' => 1,
                'max_days' => 3,
                'weight_limit' => 20,
                'docs_url' => 'https://yandex.ru/dev/logistics/delivery/',
                'api_settings' => ['oauth_token' => ''],
            ],
            [
                'code' => 'pickup',
                'title' => 'Самовывоз',
                'description' => 'Забрать заказ самостоятельно из пункта выдачи.',
                'type' => 'pickup',
                'price' => 0,
                'min_days' => 0,
                'max_days' => 1,
                'weight_limit' => null,
                'docs_url' => null,
                'api_settings' => [],
            ],
            [
                'code' => 'courier_local',
                'title' => 'Курьер по городу',
                'description' => 'Доставка курьером в пределах города.',
                'type' => 'courier',
                'price' => 300,
                'min_days' => 0,
                'max_days' => 2,
                'weight_limit' => 25,
                'docs_url' => null,
                'api_settings' => [],
            ],
        ];
    }

    /**
     * Какие реквизиты нужны какой службе — единый источник для сидера и
     * формы. Форма показывает только поля выбранной службы, а не все сразу.
     *
     * @return array<string, list<string>>
     */
    public static function credentialFields(): array
    {
        $fields = [];

        foreach (self::definitions() as $definition) {
            $fields[$definition['code']] = array_keys($definition['api_settings']);
        }

        return $fields;
    }

    /**
     * @return int сколько методов реально добавлено
     */
    public static function seed(bool $reset = false): int
    {
        if (! class_exists(DeliveryMethod::class)) {
            return 0;
        }

        $created = 0;

        foreach (self::definitions() as $index => $definition) {
            $existing = DeliveryMethod::where('code', $definition['code'])->first();

            if ($existing && ! $reset) {
                continue;
            }

            $attributes = [
                'title' => $definition['title'],
                'description' => $definition['description'],
                'type' => $definition['type'],
                'price' => $definition['price'],
                'min_days' => $definition['min_days'],
                'max_days' => $definition['max_days'],
                'weight_limit' => $definition['weight_limit'],
                'docs_url' => $definition['docs_url'],
                'api_settings' => $definition['api_settings'],
                // Интеграция выключена: без ключей она всё равно не работает,
                // а включённая пустая пыталась бы ходить в API на каждом заказе.
                'api_enabled' => false,
                'regions' => [DeliveryMethod::ALL_REGIONS],
                'is_russian' => true,
                // Выключен: включённый метод без настроек показался бы
                // покупателю в корзине и посчитал бы доставку неверно.
// Включены службы, которые работают и без ключей: цена у них
                // фиксированная, расчёт по API остаётся выключенным. Без
                // единого способа доставки оформить заказ невозможно.
                'active' => in_array($definition['code'], self::ENABLED_BY_DEFAULT, true),
                'sort_order' => $index,
            ];

            if ($existing) {
                $existing->update($attributes);
                continue;
            }

            DeliveryMethod::create($attributes + ['code' => $definition['code']]);
            $created++;
        }

        return $created;
    }
}
