<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Причина отмены заказа.
 *
 * Понадобилась вместе с отменой по сроку оплаты: покупателю нужно объяснить,
 * ПОЧЕМУ заказ отменён, а письмо о смене статуса одно на все переходы и без
 * этого поля написало бы просто «Отменён». Разница существенная: «отменён
 * магазином» и «оплата не поступила за 10 минут, товар вернулся в продажу»
 * требуют от покупателя разных действий.
 *
 * Владельцу поле полезно тем же: в панели видно, отменил ли заказ человек
 * или его закрыл таймер.
 *
 * Строка, а не перечисление: значений пока два, а заводить ради этого
 * отдельный тип в базе (и миграцию на каждое новое значение) незачем.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'cancel_reason')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('cancel_reason', 50)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'cancel_reason')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });
    }
};
