<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💳 Платежи и доставка - объединенная миграция
 */
return new class extends Migration
{
    public function up(): void
    {
        // Методы оплаты
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('offline');
            $table->boolean('active')->default(true);
            $table->json('settings')->nullable();
            $table->string('code')->nullable()->index();
            $table->boolean('is_russian')->default(false);
            $table->decimal('commission', 5, 2)->nullable();
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();
            $table->json('currencies')->nullable();
            $table->boolean('test_mode')->default(false);
            $table->timestamps();
        });

        // Методы доставки
        Schema::create('delivery_methods', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->string('code')->nullable()->index();
            $table->boolean('is_russian')->default(false);
            $table->boolean('api_enabled')->default(false);
            $table->json('api_settings')->nullable();
            $table->string('type')->default('courier');
            $table->unsignedInteger('min_days')->nullable();
            $table->unsignedInteger('max_days')->nullable();
            $table->decimal('weight_limit', 8, 2)->nullable();
            $table->json('regions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_methods');
        Schema::dropIfExists('payment_methods');
    }
};

