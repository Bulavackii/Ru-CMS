<?php

namespace Modules\Payments\Gateways;

use Modules\Payments\Models\Order;
use Modules\Payments\Models\PaymentMethod;
use Illuminate\Support\Facades\Log;

/**
 * 💳 Абстрактный класс платежного гейтвея
 */
abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    protected PaymentMethod $paymentMethod;
    protected array $config;

    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->config = $paymentMethod->settings ?? [];
    }

    /**
     * Получить значение настройки
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Проверить, включен ли тестовый режим
     */
    protected function isTestMode(): bool
    {
        return $this->paymentMethod->test_mode ?? false;
    }

    /**
     * Получить URL для редиректа после успешной оплаты
     */
    protected function getSuccessUrl(Order $order): string
    {
        return $this->getConfig('success_url') 
            ?? route('payments.success', ['order' => $order->id]);
    }

    /**
     * Получить URL для редиректа после неудачной оплаты
     */
    protected function getFailUrl(Order $order): string
    {
        return $this->getConfig('fail_url') 
            ?? route('payments.fail', ['order' => $order->id]);
    }

    /**
     * Получить URL для webhook
     */
    protected function getWebhookUrl(): string
    {
        return $this->getConfig('webhook_url') 
            ?? route('payment.webhook', ['gateway' => $this->getGatewayCode()]);
    }

    /**
     * Получить код гейтвея
     */
    abstract protected function getGatewayCode(): string;

    /**
     * Сырое тело текущего запроса — то, что платёжная система ПОДПИСЫВАЛА.
     *
     * ⚠️ Пересобирать подпись из разобранного массива нельзя. `json_encode()`
     * даёт другой порядок ключей, другое экранирование юникода и другой вид
     * чисел, чем прислал отправитель, — подпись не совпадёт НИКОГДА. Два
     * драйвера так и делали: проверка у них всегда была ложной, то есть
     * уведомления не принимались вовсе, и выглядело это как «оплата не
     * доходит».
     *
     * Драйвер выполняется внутри того самого запроса, поэтому тело и
     * заголовки берутся прямо из него.
     */
    protected function rawBody(): string
    {
        return (string) request()->getContent();
    }

    /** Заголовок текущего запроса (подпись обычно приходит именно так). */
    protected function requestHeader(string $имя): string
    {
        return (string) request()->header($имя, '');
    }

    /**
     * 🔴 Сумма платежа совпадает с суммой заказа?
     *
     * Подпись уведомления подтверждает ОТПРАВИТЕЛЯ, но не сумму. Без этой
     * сверки покупатель, подменивший сумму на платёжной странице, получал бы
     * заказ на пятнадцать тысяч, заплатив рубль: банк честно подписал бы своё
     * уведомление о рубле, а мы честно перевели бы заказ в «Оплачен».
     *
     * Копейки сравниваем с допуском: суммы приходят строками и числами
     * с плавающей точкой, и «1000.00» с «1000» побайтово не равны.
     *
     * @param float $оплачено сумма из ответа платёжной системы, в рублях
     */
    protected function amountMatches(?Order $order, float $оплачено): bool
    {
        if (! $order) {
            return false;
        }

        if (abs($оплачено - (float) $order->total) <= 0.01) {
            return true;
        }

        $this->logError('Сумма платежа не совпадает с суммой заказа', [
            'order_id' => $order->id,
            'expected' => (float) $order->total,
            'paid' => $оплачено,
        ]);

        return false;
    }

    /**
     * Сверить подпись уведомления.
     *
     * ⚠️ Пустой ключ — это НЕ «подпись совпала». Раньше проверка сводилась к
     * сравнению двух строк: при незаполненном секретном ключе злоумышленник
     * считал ту же подпись сам (ключа-то нет) и объявлял любой заказ
     * оплаченным. Незаполненный ключ обязан ЗАКРЫВАТЬ приём уведомлений.
     *
     * Сравнение — `hash_equals`: обычное `!==` выходит из цикла на первом
     * несовпавшем байте и по времени ответа выдаёт, сколько символов уже
     * угадано.
     */
    protected function signatureMatches(?string $ключ, string $ожидается, string $получено): bool
    {
        if (empty($ключ)) {
            $this->logError('Секретный ключ не задан — уведомление не принимается');

            return false;
        }

        if (! hash_equals($ожидается, $получено)) {
            $this->logError('Подпись уведомления не совпала');

            return false;
        }

        return true;
    }

    /**
     * Логирование
     */
    protected function log(string $message, array $context = []): void
    {
        Log::info("[{$this->getGatewayCode()}] {$message}", $context);
    }

    /**
     * Логирование ошибок
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->getGatewayCode()}] {$message}", $context);
    }
}

