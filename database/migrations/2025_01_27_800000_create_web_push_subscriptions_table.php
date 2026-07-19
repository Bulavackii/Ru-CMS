<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📱 Миграция для хранения Web Push подписок
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('endpoint', 500)->unique(); // URL endpoint для отправки
            $table->string('public_key')->nullable(); // Публичный ключ клиента
            $table->string('auth_token')->nullable(); // Токен аутентификации
            $table->string('user_agent')->nullable(); // Браузер пользователя
            $table->string('ip_address', 45)->nullable(); // IP адрес
            $table->boolean('active')->default(true); // Активна ли подписка
            $table->timestamp('last_notified_at')->nullable(); // Последнее уведомление
            $table->timestamps();

            $table->index(['user_id', 'active']);
            $table->index('endpoint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_push_subscriptions');
    }
};

