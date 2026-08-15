<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Отметка «остатки по этому заказу уже возвращены».
 *
 * Возврат остатка жил ТОЛЬКО в удалении заказа, а отмена его не возвращала
 * вовсе — отменённый заказ навсегда списывал товар со склада. Как только
 * возврат появляется и в отмене, возникает вторая беда: отменить заказ, а
 * потом удалить — и остаток вернётся ДВАЖДЫ, то есть склад вырастет из
 * ниоткуда. Отметка делает возврат разовым независимо от того, сколько раз
 * и какими путями по заказу прошлись.
 *
 * Время, а не флаг: по нему видно, когда именно вернули, — при разборе
 * расхождений на складе это первое, что спрашивают.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'stock_returned_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_returned_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'stock_returned_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_returned_at');
        });
    }
};
