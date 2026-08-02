<?php

namespace Modules\Payments\Console\Commands;

use Illuminate\Console\Command;
use Modules\Payments\Models\PaymentMethod;

/**
 * Популярные в РФ способы оплаты, создаваемые при установке CMS.
 *
 * ⚠️ Методы создаются ВЫКЛЮЧЕННЫМИ и БЕЗ ключей. Боевые реквизиты
 * (shopId, секретный ключ, terminalKey) в коде не хранятся и храниться
 * не должны: репозиторий публичный. Владелец вводит их сам в панели,
 * а docs_url подсказывает, где эти ключи выдают.
 *
 * Идемпотентно: повторный запуск не плодит дубли и НЕ перетирает то,
 * что владелец уже настроил, — сверка идёт по коду метода.
 */
class SeedDefaultPaymentMethodsCommand extends Command
{
    protected $signature = 'payments:seed-default {--reset : Пересоздать методы, потеряв настройки}';

    protected $description = 'Создать типовые для РФ способы оплаты (выключенными, без ключей)';

    public function handle(): int
    {
        $created = self::seed((bool) $this->option('reset'));

        $this->info($created > 0
            ? "Добавлено способов оплаты: {$created}"
            : 'Все типовые способы оплаты уже заведены — ничего не меняли.');

        return self::SUCCESS;
    }

    /**
     * Описания методов. Ключи settings пустые намеренно: это подсказка
     * формы о том, какие поля нужны драйверу, а не место для боевых ключей.
     */
    public static function definitions(): array
    {
        return [
            [
                'code' => 'yookassa',
                'title' => 'ЮKassa',
                'description' => 'Карты, СБП, кошельки и рассрочка через ЮKassa.',
                'type' => 'yookassa',
                'commission' => 3.5,
                'docs_url' => 'https://yookassa.ru/developers',
                'settings' => ['shop_id' => '', 'secret_key' => ''],
            ],
            [
                'code' => 'sbp',
                'title' => 'СБП',
                'description' => 'Оплата по QR-коду через Систему быстрых платежей.',
                'type' => 'sbp',
                'commission' => 0.7,
                'docs_url' => 'https://sbp.nspk.ru/',
                'settings' => ['merchant_id' => '', 'account' => ''],
            ],
            [
                'code' => 'sberpay',
                'title' => 'SberPay',
                'description' => 'Оплата в приложении Сбербанка.',
                'type' => 'sberpay',
                'commission' => 2.5,
                'docs_url' => 'https://developers.sber.ru/portal/products/acquiring',
                'settings' => ['username' => '', 'password' => ''],
            ],
            [
                // Тинькофф переименован в Т-Банк в 2024 году, эквайринг —
                // T-Bank Acquiring. Старое имя не используем.
                'code' => 'tbank',
                'title' => 'Т-Банк',
                'description' => 'Эквайринг Т-Банка: карты, T-Pay, СБП.',
                'type' => 'tbank',
                'commission' => 2.9,
                'docs_url' => 'https://developer.tbank.ru/eacq/api',
                'settings' => ['terminal_key' => '', 'password' => ''],
            ],
            [
                'code' => 'cash',
                'title' => 'Наличными при получении',
                'description' => 'Оплата курьеру или в пункте выдачи.',
                'type' => 'offline',
                'commission' => 0,
                'docs_url' => null,
                'settings' => [],
            ],
            [
                'code' => 'bank_transfer',
                'title' => 'Банковский перевод',
                'description' => 'Счёт для оплаты по реквизитам, для юридических лиц.',
                'type' => 'offline',
                'commission' => 0,
                'docs_url' => null,
                'settings' => [],
            ],
        ];
    }

    /**
     * @return int сколько методов реально добавлено
     */
    public static function seed(bool $reset = false): int
    {
        if (! class_exists(PaymentMethod::class)) {
            return 0;
        }

        $created = 0;

        foreach (self::definitions() as $index => $definition) {
            $existing = PaymentMethod::where('code', $definition['code'])->first();

            if ($existing && ! $reset) {
                continue;
            }

            $attributes = [
                'title' => $definition['title'],
                'description' => $definition['description'],
                'type' => $definition['type'],
                'commission' => $definition['commission'],
                'docs_url' => $definition['docs_url'],
                'settings' => $definition['settings'],
                'currencies' => ['RUB'],
                'is_russian' => true,
                'test_mode' => true,
                // Выключен: без ключей метод всё равно не работает, а
                // включённый пустой метод показался бы покупателю в корзине.
                'active' => false,
                'sort_order' => $index,
            ];

            if ($existing) {
                $existing->update($attributes);
                continue;
            }

            PaymentMethod::create($attributes + ['code' => $definition['code']]);
            $created++;
        }

        return $created;
    }
}
