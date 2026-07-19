<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛒 Заказы - полная миграция (orders + items)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Таблица заказов
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->foreignId('delivery_method_id')->nullable()->constrained('delivery_methods')->nullOnDelete();
            $table->decimal('total', 10, 2);
            $table->decimal('items_total', 10, 2)->default(0);
            $table->decimal('delivery_price', 10, 2)->default(0);
            $table->decimal('commission', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->boolean('is_new')->default(true);
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // Элементы заказа
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // product_id может ссылаться на products, но не обязательно (может быть удален товар)
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->integer('qty');
            $table->timestamps();
            
            // Индекс для быстрого поиска
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

