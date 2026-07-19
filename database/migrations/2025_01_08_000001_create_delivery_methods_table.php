<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 2025_01_08_000000_create_payment_methods_table.php уже создаёт эту
        // таблицу ("Платежи и доставка - объединенная миграция") — на свежей
        // установке она приходит первой и эта миграция тут лишняя.
        if (Schema::hasTable('delivery_methods')) {
            return;
        }

        Schema::create('delivery_methods', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('delivery_methods');
    }
};
