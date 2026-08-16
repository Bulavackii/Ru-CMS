<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\News\Models\News;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * 🔴 Уведомления платёжных систем: что должно и чего не должно принимать.
 *
 * Маршрут `POST /payment/webhook/{gateway}` публичный и без CSRF — иначе
 * платёжная система до него не достучится. Значит, единственная защита это
 * подпись и переспрос у самой системы, а решение об оплате принимается по
 * ответу системы, а не по телу запроса.
 *
 * ⚠️ Тут проверяется ИМЕННО ПУТЬ ЧЕРЕЗ HTTP, а не вызов драйвера напрямую:
 * в документации до сих пор стояло «end-to-end не проверен». Маршруты модуля
 * Payments в тестах грузятся (модуль в `$legacyModules`), так что проверить
 * можно по-настоящему.
 *
 * Общее правило всех проверок ниже: **успехом считается только то, что заказ
 * НЕ стал оплаченным**. Драйверу позволено промолчать, ответить «ignored»,
 * записать в журнал — но не выдать товар бесплатно.
 */
class PaymentWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function способ(string $код, array $настройки = []): PaymentMethod
    {
        return PaymentMethod::create([
            'title' => 'Способ ' . $код,
            'code' => $код,
            'type' => $код,
            'active' => true,
            'commission' => 0,
            'settings' => $настройки,
        ]);
    }

    /** Заказ на 15 000 ₽ с одним товаром — по нему и пытаются заплатить рубль. */
    private function заказ(PaymentMethod $способ, int $остаток = 5): Order
    {
        $товар = News::create([
            'title' => 'Дорогой товар', 'slug' => 'dorogoy-' . uniqid(),
            'content' => '<p>x</p>', 'template' => 'products',
            'published' => true, 'price' => 15000, 'stock' => $остаток,
        ]);

        $заказ = Order::create([
            'payment_method_id' => $способ->id,
            'status' => 'pending',
            'customer_name' => 'Иван',
            'items_total' => 15000, 'delivery_price' => 0, 'commission' => 0, 'total' => 15000,
        ]);

        OrderItem::create([
            'order_id' => $заказ->id, 'product_id' => $товар->id,
            'title' => $товар->title, 'qty' => 1, 'price' => 15000,
        ]);

        return $заказ;
    }

    private function неОплачен(Order $заказ, string $почему): void
    {
        $this->assertNotSame('completed', $заказ->fresh()->status, $почему);
    }

    /* ─────────────── Т-Банк: подпись ─────────────── */

    private function подписьТБанк(array $данные, string $ключ): string
    {
        unset($данные['Token'], $данные['Receipt']);
        ksort($данные);

        $строка = '';
        foreach ($данные as $к => $з) {
            $строка .= $к . ':' . (is_array($з) ? json_encode($з, JSON_UNESCAPED_UNICODE) : $з) . ';';
        }

        return hash('sha256', $строка . $ключ);
    }

    /** Уведомление без подписи заказ не оплачивает. */
    public function test_tbank_rejects_unsigned_notification(): void
    {
        $способ = $this->способ('tbank', ['secret_key' => 'настоящий-ключ']);
        $заказ = $this->заказ($способ);

        $this->postJson('/payment/webhook/tbank', [
            'Status' => 'CONFIRMED',
            'OrderId' => (string) $заказ->id,
            'Amount' => 1500000,
        ]);

        $this->неОплачен($заказ, 'Заказ оплачен уведомлением без подписи');
    }

    /**
     * 🔴 Подпись верна, а сумма чужая — заказ НЕ оплачивается.
     *
     * Подпись подтверждает отправителя, но не сумму. Без сверки покупатель
     * платил рубль за заказ на пятнадцать тысяч.
     */
    public function test_tbank_rejects_correct_signature_with_wrong_amount(): void
    {
        $ключ = 'настоящий-ключ';
        $способ = $this->способ('tbank', ['secret_key' => $ключ]);
        $заказ = $this->заказ($способ);

        $данные = [
            'Status' => 'CONFIRMED',
            'OrderId' => (string) $заказ->id,
            'Amount' => 100,          // рубль в копейках
            'PaymentId' => '777',
        ];
        $данные['Token'] = $this->подписьТБанк($данные, $ключ);

        $this->postJson('/payment/webhook/tbank', $данные);

        $this->неОплачен($заказ, 'Заказ на 15 000 оплачен рублём');
    }

    /** Верная подпись и верная сумма — заказ оплачивается. */
    public function test_tbank_accepts_correct_signature_and_amount(): void
    {
        $ключ = 'настоящий-ключ';
        $способ = $this->способ('tbank', ['secret_key' => $ключ]);
        $заказ = $this->заказ($способ);

        $данные = [
            'Status' => 'CONFIRMED',
            'OrderId' => (string) $заказ->id,
            'Amount' => 1500000,
            'PaymentId' => '777',
        ];
        $данные['Token'] = $this->подписьТБанк($данные, $ключ);

        $this->postJson('/payment/webhook/tbank', $данные);

        $this->assertSame('completed', $заказ->fresh()->status, 'Честное уведомление не приняли');
    }

    /**
     * 🔴 Незаполненный секретный ключ ЗАКРЫВАЕТ приём.
     *
     * Раньше проверка сводилась к сравнению двух строк: при пустом ключе
     * злоумышленник считал ту же подпись сам и объявлял заказ оплаченным.
     */
    public function test_tbank_empty_secret_blocks_everything(): void
    {
        $способ = $this->способ('tbank', ['secret_key' => '']);
        $заказ = $this->заказ($способ);

        $данные = [
            'Status' => 'CONFIRMED',
            'OrderId' => (string) $заказ->id,
            'Amount' => 1500000,
        ];
        $данные['Token'] = $this->подписьТБанк($данные, '');   // считаем сами — ключа-то нет

        $this->postJson('/payment/webhook/tbank', $данные);

        $this->неОплачен($заказ, 'Пустой ключ позволил подделать уведомление');
    }

    /* ─────────────── Робокасса ─────────────── */

    /** Подпись верна, сумма занижена — не принимаем. */
    public function test_robokassa_rejects_wrong_amount(): void
    {
        $пароль = 'пароль2';
        $способ = $this->способ('robokassa', ['password_2' => $пароль]);
        $заказ = $this->заказ($способ);

        $this->postJson('/payment/webhook/robokassa', [
            'OutSum' => '1.00',
            'InvId' => (string) $заказ->id,
            'SignatureValue' => strtoupper(md5("1.00:{$заказ->id}:{$пароль}")),
        ]);

        $this->неОплачен($заказ, 'Робокасса: заказ оплачен рублём при верной подписи');
    }

    /** Верная сумма — принимаем. */
    public function test_robokassa_accepts_full_amount(): void
    {
        $пароль = 'пароль2';
        $способ = $this->способ('robokassa', ['password_2' => $пароль]);
        $заказ = $this->заказ($способ);

        $this->postJson('/payment/webhook/robokassa', [
            'OutSum' => '15000.00',
            'InvId' => (string) $заказ->id,
            'SignatureValue' => strtoupper(md5("15000.00:{$заказ->id}:{$пароль}")),
        ]);

        $this->assertSame('completed', $заказ->fresh()->status);
    }

    /* ─────────────── CloudPayments: подпись по СЫРОМУ телу ─────────────── */

    /**
     * Подпись считается по сырому телу — и теперь совпадает.
     *
     * ⚠️ Раньше драйвер пересобирал тело через `json_encode()` от разобранного
     * массива: другой порядок ключей и экранирование, подпись не совпадала
     * НИКОГДА. То есть оплата через CloudPayments не подтверждалась вовсе, и
     * выглядело это как «уведомления не доходят».
     */
    public function test_cloudpayments_signature_is_computed_over_the_raw_body(): void
    {
        $ключ = 'секрет';
        $способ = $this->способ('cloudpayments', ['api_secret' => $ключ]);
        $заказ = $this->заказ($способ);

        // Кириллица в теле — как раз то, на чём разъезжалось экранирование
        $тело = json_encode([
            'Status' => 'Completed',
            'InvoiceId' => (string) $заказ->id,
            'Amount' => 15000,
            'TransactionId' => '42',
            'Description' => 'Заказ №' . $заказ->id,
        ], JSON_UNESCAPED_UNICODE);

        $this->call(
            'POST', '/payment/webhook/cloudpayments', [], [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_HMAC' => base64_encode(hash_hmac('sha256', $тело, $ключ, true)),
            ],
            $тело
        );

        $this->assertSame('completed', $заказ->fresh()->status, 'Честное уведомление не приняли');
    }

    /** Чужая подпись — не принимаем. */
    public function test_cloudpayments_rejects_foreign_signature(): void
    {
        $способ = $this->способ('cloudpayments', ['api_secret' => 'секрет']);
        $заказ = $this->заказ($способ);

        $тело = json_encode(['Status' => 'Completed', 'InvoiceId' => (string) $заказ->id, 'Amount' => 15000]);

        $this->call(
            'POST', '/payment/webhook/cloudpayments', [], [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_HMAC' => base64_encode(hash_hmac('sha256', $тело, 'НЕ тот ключ', true)),
            ],
            $тело
        );

        $this->неОплачен($заказ, 'Принято уведомление, подписанное чужим ключом');
    }

    /** Подпись верна, сумма занижена — не принимаем. */
    public function test_cloudpayments_rejects_wrong_amount(): void
    {
        $ключ = 'секрет';
        $способ = $this->способ('cloudpayments', ['api_secret' => $ключ]);
        $заказ = $this->заказ($способ);

        $тело = json_encode(['Status' => 'Completed', 'InvoiceId' => (string) $заказ->id, 'Amount' => 1]);

        $this->call(
            'POST', '/payment/webhook/cloudpayments', [], [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_HMAC' => base64_encode(hash_hmac('sha256', $тело, $ключ, true)),
            ],
            $тело
        );

        $this->неОплачен($заказ, 'CloudPayments: заказ на 15 000 оплачен рублём');
    }

    /* ─────────────── ЮKassa: решение по ответу API ─────────────── */

    /**
     * Тело уведомления не имеет значения — статус переспрашивается у ЮKassa.
     *
     * Здесь API отвечает «оплачен, но на рубль»: заказ оплаченным не станет.
     */
    public function test_yookassa_rejects_amount_reported_by_the_api(): void
    {
        $способ = $this->способ('yookassa', ['shop_id' => '1', 'secret_key' => 'к']);
        $заказ = $this->заказ($способ);

        Http::fake(['api.yookassa.ru/*' => Http::response([
            'id' => 'pay-1',
            'status' => 'succeeded',
            'paid' => true,
            'amount' => ['value' => '1.00', 'currency' => 'RUB'],
            'metadata' => ['order_id' => $заказ->id],
        ])]);

        $this->postJson('/payment/webhook/yookassa', [
            'event' => 'payment.succeeded',
            'object' => ['id' => 'pay-1'],
        ]);

        $this->неОплачен($заказ, 'ЮKassa: заказ на 15 000 оплачен рублём');
    }

    /** Сумма сошлась — заказ оплачен. */
    public function test_yookassa_accepts_full_amount(): void
    {
        $способ = $this->способ('yookassa', ['shop_id' => '1', 'secret_key' => 'к']);
        $заказ = $this->заказ($способ);

        Http::fake(['api.yookassa.ru/*' => Http::response([
            'id' => 'pay-1',
            'status' => 'succeeded',
            'paid' => true,
            'amount' => ['value' => '15000.00', 'currency' => 'RUB'],
            'metadata' => ['order_id' => $заказ->id],
        ])]);

        $this->postJson('/payment/webhook/yookassa', [
            'event' => 'payment.succeeded',
            'object' => ['id' => 'pay-1'],
        ]);

        $this->assertSame('completed', $заказ->fresh()->status);
    }

    /**
     * Тело говорит «оплачено», API — «не оплачено». Верим API.
     *
     * Это исходная дыра: раньше решение принималось по телу запроса, и
     * достаточно было прислать `payment.succeeded` с чужим order_id.
     */
    public function test_yookassa_believes_the_api_not_the_request_body(): void
    {
        $способ = $this->способ('yookassa', ['shop_id' => '1', 'secret_key' => 'к']);
        $заказ = $this->заказ($способ);

        Http::fake(['api.yookassa.ru/*' => Http::response([
            'id' => 'pay-1',
            'status' => 'pending',
            'paid' => false,
            'amount' => ['value' => '15000.00', 'currency' => 'RUB'],
            'metadata' => ['order_id' => $заказ->id],
        ])]);

        $this->postJson('/payment/webhook/yookassa', [
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'pay-1',
                'paid' => true,
                'amount' => ['value' => '15000.00'],
                'metadata' => ['order_id' => $заказ->id],
            ],
        ]);

        $this->неОплачен($заказ, 'Принято на веру тело запроса, а не ответ платёжной системы');
    }

    /* ─────────────── Сбербанк ─────────────── */

    /** Банк подтвердил оплату, но на другую сумму — не принимаем. */
    public function test_sberbank_rejects_wrong_amount(): void
    {
        $способ = $this->способ('sberpay', ['user_name' => 'u', 'password' => 'p']);
        $заказ = $this->заказ($способ);

        Http::fake(['*sberbank.ru/*' => Http::response([
            'errorCode' => '0',
            'orderStatus' => 2,
            'amount' => 100,          // рубль в копейках
        ])]);

        $this->postJson('/payment/webhook/sberpay', [
            'orderNumber' => (string) $заказ->id,
            'orderId' => 'bank-1',
        ]);

        $this->неОплачен($заказ, 'Сбербанк: заказ на 15 000 оплачен рублём');
    }

    /* ─────────────── Общее ─────────────── */

    /**
     * Уведомление о неизвестном способе не роняет обработчик.
     *
     * Адрес публичный, по нему стучатся сканеры и чужие системы: пятисотка
     * здесь означала бы, что любой прохожий может засыпать журнал ошибками.
     */
    public function test_unknown_gateway_does_not_crash(): void
    {
        $this->postJson('/payment/webhook/neizvestnaya-sistema', ['foo' => 'bar'])
            ->assertOk();
    }

    /**
     * ⚠️ Ключи платёжной системы не уходят в журнал.
     *
     * Заголовки писались ЦЕЛИКОМ, а платёжные системы кладут в них подпись и
     * ключ доступа. Файл журнала попадает в бэкапы и в выгрузки для поддержки
     * заметно легче, чем база.
     */
    public function test_webhook_does_not_log_authorization_headers(): void
    {
        $исходник = file_get_contents(base_path('modules/Payments/Controllers/Admin/OrderController.php'));

        $начало = strpos($исходник, 'public function webhook');
        $кусок = substr($исходник, $начало, 900);

        $this->assertStringNotContainsString(
            'headers->all()',
            $кусок,
            'Уведомление пишет в журнал все заголовки — там подпись и ключ доступа'
        );
    }
}
