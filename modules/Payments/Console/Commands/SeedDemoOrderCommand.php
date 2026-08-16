<?php

namespace Modules\Payments\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Delivery\Models\DeliveryMethod;
use Modules\Payments\Models\Order;
use Modules\Payments\Models\OrderItem;
use Modules\Payments\Models\PaymentMethod;

/**
 * Демо-заказ: один, но настоящий.
 *
 * Раздел «Заказы» после установки был пуст, и увидеть его в работе можно было
 * только оформив покупку руками. Пустой список к тому же ничего не говорит о
 * том, как раздел выглядит с данными — а именно там вылезают и переполнение
 * по ширине, и разъехавшиеся колонки.
 *
 * Заказ собирается из того, что на сайте уже есть: товар берётся из
 * материалов с шаблоном «Товары», способы оплаты и доставки — из включённых.
 * Поэтому он выглядит как настоящий, а не как строка с прочерками.
 *
 * ⚠️ Остаток товара НЕ списывается. Демо-заказ — витрина раздела, а не
 * покупка: списание сделало бы расхождение между складом и продажами на
 * свежей установке.
 */
class SeedDemoOrderCommand extends Command
{
    protected $signature = 'orders:seed-demo {--reset : пересоздать демо-заказ}';

    protected $description = 'Завести демонстрационный заказ (идемпотентно)';

    /** По этой пометке демо-заказ отличается от настоящих. */
    public const МЕТКА = 'Демонстрационный заказ. Его можно удалить.';

    public function handle(): int
    {
        $итог = self::seed((bool) $this->option('reset'));

        $this->info($итог === null
            ? 'Демо-заказ не заведён: нет включённых способов оплаты или доставки.'
            : "Демо-заказ №{$итог->id} готов.");

        return self::SUCCESS;
    }

    /**
     * Завести заказ, если его ещё нет.
     *
     * @return Order|null null — если заводить не из чего
     */
    public static function seed(bool $пересоздать = false): ?Order
    {
        $существующий = Order::query()->where('comment', self::МЕТКА)->first();

        if ($существующий && ! $пересоздать) {
            return $существующий;
        }

        $оплата   = PaymentMethod::query()->where('active', true)->orderBy('id')->first();
        $доставка = DeliveryMethod::query()->where('active', true)->orderBy('id')->first();

        if (! $оплата || ! $доставка) {
            return null;
        }

        // Товары берём из материалов: цена и название должны совпадать с тем,
        // что покупатель видит на сайте.
        $товары = DB::table('news')
            ->whereIn('template', ['products', 'ourworks'])
            ->whereNotNull('price')
            ->where('published', true)
            ->orderBy('id')
            ->limit(2)
            ->get(['id', 'title', 'price']);

        if ($товары->isEmpty()) {
            return null;
        }

        if ($существующий) {
            $существующий->items()->delete();
            $существующий->delete();
        }

        $ценаДоставки = (float) ($доставка->price ?? 0);
        $суммаТоваров = 0.0;

        $заказ = Order::create([
            'payment_method_id'  => $оплата->id,
            'delivery_method_id' => $доставка->id,
            'status'             => 'completed',
            'is_new'             => false,
            'customer_name'      => 'Иван Петров',
            'customer_phone'     => '+7 900 000-00-00',
            'customer_email'     => 'ivan@example.com',
            'customer_address'   => 'г. Курск, ул. Ленина, д. 1, кв. 2',
            'comment'            => self::МЕТКА,
            'items_total'        => 0,
            'delivery_price'     => $ценаДоставки,
            'commission'         => 0,
            'total'              => 0,
        ]);

        foreach ($товары as $номер => $товар) {
            $количество = $номер === 0 ? 2 : 1;
            $суммаТоваров += (float) $товар->price * $количество;

            OrderItem::create([
                'order_id'   => $заказ->id,
                'product_id' => $товар->id,
                'title'      => $товар->title,
                'price'      => $товар->price,
                'qty'        => $количество,
            ]);
        }

        // Суммы проставляем ПОСЛЕ строк: до них считать нечего.
        $заказ->forceFill([
            'items_total' => $суммаТоваров,
            'total'       => $суммаТоваров + $ценаДоставки,
        ])->saveQuietly();

        return $заказ->fresh();
    }
}
