<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('title'); // Название способа оплаты
                $table->text('description')->nullable(); // Описание
                $table->string('type')->default('offline'); // Тип: offline или online
                $table->boolean('active')->default(true); // Включён ли метод
                $table->json('settings')->nullable(); // Настройки API, параметры и пр.
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
