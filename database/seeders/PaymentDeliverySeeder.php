<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Payments\Models\PaymentMethod;
use Modules\Delivery\Models\DeliveryMethod;

class PaymentDeliverySeeder extends Seeder
{
    public function run(): void
    {
        // 💳 СЕЕМ РОССИЙСКИЕ ПЛАТЕЖНЫЕ СИСТЕМЫ
        $paymentMethods = [
            // 🇷🇺 СБП (Система быстрых платежей)
            [
                'title' => 'СБП (Система быстрых платежей)',
                'description' => 'Мгновенный перевод по номеру телефона через мобильный банк. Комиссия 0%',
                'type' => 'sbp',
                'code' => 'sbp',
                'is_russian' => true,
                'active' => true,
                'commission' => 0,
                'min_amount' => 1.00,
                'max_amount' => 100000.00,
                'currencies' => ['RUB'],
                'settings' => [
                    'commission' => 0,
                    'instant' => true,
                    'banks' => ['Сбербанк', 'Тинькофф', 'ВТБ', 'Альфа-Банк', 'Райффайзен']
                ],
            ],

            // 💳 ЮKassa
            [
                'title' => 'ЮKassa',
                'description' => 'Популярный российский эквайринг. Поддержка карт, кошельков и СБП',
                'type' => 'yookassa',
                'code' => 'yookassa',
                'is_russian' => true,
                'active' => true,
                'commission' => 2.9,
                'min_amount' => 1.00,
                'max_amount' => 600000.00,
                'currencies' => ['RUB', 'USD', 'EUR'],
                'settings' => [
                    'commission' => 2.9,
                    'api_key' => '',
                    'shop_id' => '',
                    'webhook_secret' => '',
                ],
            ],

            // 🏦 Тинькофф Касса
            [
                'title' => 'Тинькофф Касса',
                'description' => 'Интернет-эквайринг от Тинькофф. Поддержка всех популярных методов оплаты',
                'type' => 'tinkoff',
                'code' => 'tinkoff',
                'is_russian' => true,
                'active' => true,
                'commission' => 2.79,
                'min_amount' => 1.00,
                'max_amount' => 300000.00,
                'currencies' => ['RUB'],
                'settings' => [
                    'commission' => 2.79,
                    'terminal_key' => '',
                    'secret_key' => '',
                    'shop_url' => '',
                ],
            ],

            // 🏦 Сбербанк Онлайн
            [
                'title' => 'Сбербанк Онлайн',
                'description' => 'Оплата через Сбербанк Онлайн и мобильное приложение',
                'type' => 'sberbank',
                'code' => 'sberbank',
                'is_russian' => true,
                'active' => true,
                'commission' => 3.5,
                'min_amount' => 1.00,
                'max_amount' => 150000.00,
                'currencies' => ['RUB'],
                'settings' => [
                    'commission' => 3.5,
                    'api_key' => '',
                    'inn' => '',
                ],
            ],

            // 💳 Сбербанк Pay
            [
                'title' => 'Сбербанк Pay',
                'description' => 'Быстрая оплата через Сбербанк Pay',
                'type' => 'sberpay',
                'code' => 'sberpay',
                'is_russian' => true,
                'active' => true,
                'commission' => 3.5,
                'min_amount' => 1.00,
                'max_amount' => 150000.00,
                'currencies' => ['RUB'],
                'settings' => [
                    'commission' => 3.5,
                    'api_key' => '',
                    'inn' => '',
                ],
            ],

            // 📱 QIWI
            [
                'title' => 'QIWI',
                'description' => 'Кошелек QIWI и терминалы оплаты',
                'type' => 'qiwi',
                'code' => 'qiwi',
                'is_russian' => true,
                'active' => true,
                'commission' => 2.0,
                'min_amount' => 1.00,
                'max_amount' => 100000.00,
                'currencies' => ['RUB'],
                'settings' => [
                    'commission' => 2.0,
                    'api_key' => '',
                    'shop_id' => '',
                ],
            ],

            // 🔄 Robokassa
            [
                'title' => 'Robokassa',
                'description' => 'Популярный агрегатор платежных систем',
                'type' => 'robokassa',
                'code' => 'robokassa',
                'is_russian' => true,
                'active' => true,
                'commission' => 2.5,
                'min_amount' => 1.00,
                'max_amount' => 500000.00,
                'currencies' => ['RUB', 'USD', 'EUR'],
                'settings' => [
                    'commission' => 2.5,
                    'shop_id' => '',
                    'secret_key' => '',
                ],
            ],

            // ☁️ CloudPayments
            [
                'title' => 'CloudPayments',
                'description' => 'Платежный сервис для онлайн-бизнеса',
                'type' => 'cloudpayments',
                'code' => 'cloudpayments',
                'is_russian' => true,
                'active' => true,
                'commission' => 2.8,
                'min_amount' => 1.00,
                'max_amount' => 300000.00,
                'currencies' => ['RUB', 'USD', 'EUR'],
                'settings' => [
                    'commission' => 2.8,
                    'public_id' => '',
                    'api_key' => '',
                ],
            ],

            // 🏦 Unitpay
            [
                'title' => 'Unitpay',
                'description' => 'Платежный сервис с поддержкой множества методов',
                'type' => 'unitpay',
                'code' => 'unitpay',
                'is_russian' => true,
                'active' => true,
                'commission' => 2.5,
                'min_amount' => 1.00,
                'max_amount' => 500000.00,
                'currencies' => ['RUB'],
                'settings' => [
                    'commission' => 2.5,
                    'shop_id' => '',
                    'secret_key' => '',
                ],
            ],

            // 🏦 Interkassa
            [
                'title' => 'Interkassa',
                'description' => 'Платежный шлюз с поддержкой криптовалют',
                'type' => 'interkassa',
                'code' => 'interkassa',
                'is_russian' => true,
                'active' => true,
                'commission' => 2.0,
                'min_amount' => 1.00,
                'max_amount' => 1000000.00,
                'currencies' => ['RUB', 'USD', 'EUR', 'BTC', 'ETH'],
                'settings' => [
                    'commission' => 2.0,
                    'shop_id' => '',
                    'secret_key' => '',
                ],
            ],

            // 💳 Банковская карта (Международная)
            [
                'title' => 'Банковская карта',
                'description' => 'Оплата банковской картой онлайн (Visa, Mastercard, Мир)',
                'type' => 'online',
                'code' => 'card',
                'is_russian' => false,
                'active' => true,
                'commission' => 2.9,
                'min_amount' => 1.00,
                'max_amount' => 75000.00,
                'currencies' => ['RUB', 'USD', 'EUR'],
                'settings' => [
                    'commission' => 2.9,
                    'supports_mir' => true,
                    'supports_visa' => true,
                    'supports_mastercard' => true,
                ],
            ],

            // 💵 Наличные при получении
            [
                'title' => 'Наличные при получении',
                'description' => 'Оплата наличными курьеру или при самовывозе',
                'type' => 'offline',
                'code' => 'cash',
                'is_russian' => false,
                'active' => true,
                'commission' => 0,
                'min_amount' => 1.00,
                'max_amount' => 500000.00,
                'currencies' => ['RUB'],
                'settings' => [
                    'commission' => 0,
                    'change_available' => true,
                ],
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::create($method);
        }

        // 🚚 СЕЕМ РОССИЙСКИЕ СЛУЖБЫ ДОСТАВКИ
        $deliveryMethods = [
            // 📦 Почта России
            [
                'title' => 'Почта России',
                'description' => 'Доставка по всей России. Сроки 3-14 дней в зависимости от региона',
                'price' => 350.00,
                'code' => 'pochta',
                'is_russian' => true,
                'api_enabled' => true,
                'active' => true,
                'type' => 'post',
                'min_days' => 3,
                'max_days' => 14,
                'weight_limit' => 30.00,
                'regions' => ['Все регионы РФ'],
                'api_settings' => [
                    'api_key' => '',
                    'api_login' => '',
                    'calculate_delivery' => true,
                    'track_number' => true,
                ],
            ],

            // 📦 СДЭК
            [
                'title' => 'СДЭК',
                'description' => 'Курьерская доставка и пункты выдачи. Сроки 1-3 дня',
                'price' => 299.00,
                'code' => 'cdek',
                'is_russian' => true,
                'api_enabled' => true,
                'active' => true,
                'type' => 'courier',
                'min_days' => 1,
                'max_days' => 3,
                'weight_limit' => 50.00,
                'regions' => ['Все регионы РФ', 'Москва', 'Санкт-Петербург'],
                'api_settings' => [
                    'api_key' => '',
                    'api_login' => '',
                    'pvz' => true,
                    'courier' => true,
                    'calculate_delivery' => true,
                ],
            ],

            // 📦 ПЭК (Первая Экспедиционная Компания)
            [
                'title' => 'ПЭК (Первая Экспедиционная Компания)',
                'description' => 'Грузоперевозки и доставка по России. Надежная служба',
                'price' => 450.00,
                'code' => 'pek',
                'is_russian' => true,
                'api_enabled' => true,
                'active' => true,
                'type' => 'courier',
                'min_days' => 2,
                'max_days' => 5,
                'weight_limit' => 100.00,
                'regions' => ['Все регионы РФ'],
                'api_settings' => [
                    'api_key' => '',
                    'contract' => '',
                    'calculate_delivery' => true,
                ],
            ],

            // 📦 Boxberry
            [
                'title' => 'Boxberry',
                'description' => 'Пункты выдачи и курьерская доставка. Более 3000 точек',
                'price' => 250.00,
                'code' => 'boxberry',
                'is_russian' => true,
                'api_enabled' => true,
                'active' => true,
                'type' => 'pickup',
                'min_days' => 1,
                'max_days' => 3,
                'weight_limit' => 20.00,
                'regions' => ['Москва', 'Санкт-Петербург', 'Крупные города'],
                'api_settings' => [
                    'api_key' => '',
                    'token' => '',
                    'pvz' => true,
                ],
            ],

            // 🚚 Курьером по Москве
            [
                'title' => 'Курьером по Москве',
                'description' => 'Доставка в день заказа или на следующий день',
                'price' => 499.00,
                'code' => 'courier_msk',
                'is_russian' => true,
                'api_enabled' => false,
                'active' => true,
                'type' => 'courier',
                'min_days' => 0,
                'max_days' => 1,
                'weight_limit' => 15.00,
                'regions' => ['Москва', 'Московская область'],
                'api_settings' => [],
            ],

            // 🚚 Курьером по Санкт-Петербургу
            [
                'title' => 'Курьером по Санкт-Петербургу',
                'description' => 'Доставка в день заказа или на следующий день',
                'price' => 399.00,
                'code' => 'courier_spb',
                'is_russian' => true,
                'api_enabled' => false,
                'active' => true,
                'type' => 'courier',
                'min_days' => 0,
                'max_days' => 1,
                'weight_limit' => 15.00,
                'regions' => ['Санкт-Петербург', 'Ленинградская область'],
                'api_settings' => [],
            ],

            // 📦 Пункт выдачи (СДЭК)
            [
                'title' => 'Пункт выдачи СДЭК',
                'description' => 'Самовывоз из пункта выдачи СДЭК. Более 5000 точек',
                'price' => 199.00,
                'code' => 'cdek_pvz',
                'is_russian' => true,
                'api_enabled' => true,
                'active' => true,
                'type' => 'pickup',
                'min_days' => 1,
                'max_days' => 3,
                'weight_limit' => 30.00,
                'regions' => ['Все регионы РФ'],
                'api_settings' => [
                    'api_key' => '',
                    'api_login' => '',
                    'pvz' => true,
                ],
            ],

            // 📦 Почтомат (Boxberry)
            [
                'title' => 'Почтомат Boxberry',
                'description' => 'Самовывоз из почтомата. Работает 24/7',
                'price' => 149.00,
                'code' => 'boxberry_mat',
                'is_russian' => true,
                'api_enabled' => true,
                'active' => true,
                'type' => 'pickup',
                'min_days' => 1,
                'max_days' => 2,
                'weight_limit' => 10.00,
                'regions' => ['Москва', 'Санкт-Петербург'],
                'api_settings' => [
                    'api_key' => '',
                    'token' => '',
                    'postamat' => true,
                ],
            ],

            // 🚛 Грузовая доставка (ПЭК)
            [
                'title' => 'Грузовая доставка ПЭК',
                'description' => 'Доставка крупногабаритных товаров. Подъем на этаж',
                'price' => 899.00,
                'code' => 'pek_cargo',
                'is_russian' => true,
                'api_enabled' => true,
                'active' => true,
                'type' => 'courier',
                'min_days' => 2,
                'max_days' => 7,
                'weight_limit' => 200.00,
                'regions' => ['Все регионы РФ'],
                'api_settings' => [
                    'api_key' => '',
                    'contract' => '',
                    'cargo' => true,
                ],
            ],

            // 🛍️ Самовывоз со склада
            [
                'title' => 'Самовывоз со склада',
                'description' => 'Самовывоз из нашего пункта выдачи (бесплатно)',
                'price' => 0.00,
                'code' => 'pickup',
                'is_russian' => false,
                'api_enabled' => false,
                'active' => true,
                'type' => 'pickup',
                'min_days' => 0,
                'max_days' => 0,
                'weight_limit' => null,
                'regions' => ['Москва'],
                'api_settings' => [],
            ],
        ];

        foreach ($deliveryMethods as $method) {
            DeliveryMethod::create($method);
        }

        $this->command->info('✅ РОССИЙСКИЕ платежные системы и службы доставки успешно добавлены!');
        $this->command->info('📊 Всего добавлено: ' . count($paymentMethods) . ' платежных систем и ' . count($deliveryMethods) . ' служб доставки');
    }
}
