<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Вес товара и город покупателя — без них правила доставки не применить.
 *
 * У службы доставки давно есть `weight_limit` и `regions`, а
 * `DeliveryCalculatorService` умеет по ним отбивать заказ. Но применить эти
 * правила при оформлении было НЕЧЕМ: у товара не было веса вовсе, а адрес
 * покупателя собирался одной свободной строкой, из которой регион не выделить.
 * Ограничения работали только через `DeliveryApiController`, то есть у чужих
 * интеграций, а в самой корзине — никогда.
 *
 * ⚠️ Оба поля НЕОБЯЗАТЕЛЬНЫЕ, и это несущее решение. Пустой вес значит «не
 * взвешиваем» (услуга, цифровой товар), а не «ноль»: заказ из одних услуг
 * ограничение по весу проходить не должен. Пустой город — старые заказы,
 * заведённые до этой правки.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (! Schema::hasColumn('news', 'weight')) {
                // Килограммы, как и `delivery_methods.weight_limit`. Три знака
                // после запятой — чтобы можно было указать граммы.
                $table->decimal('weight', 8, 3)->nullable()->after('stock');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'customer_city')) {
                $table->string('customer_city', 190)->nullable()->after('customer_address');
            }

            if (! Schema::hasColumn('orders', 'total_weight')) {
                // Вес заказа считается при оформлении и сохраняется: пересчёт
                // задним числом дал бы другое число, если товар потом изменили,
                // а по нему владелец объясняется со службой доставки.
                $table->decimal('total_weight', 10, 3)->nullable()->after('customer_city');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'weight')) {
                $table->dropColumn('weight');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['customer_city', 'total_weight'] as $колонка) {
                if (Schema::hasColumn('orders', $колонка)) {
                    $table->dropColumn($колонка);
                }
            }
        });
    }
};
