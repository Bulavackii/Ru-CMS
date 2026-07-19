<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📊 История входов пользователей
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable(); // Email для случаев, когда user_id может быть null
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status')->default('success'); // success, failed, blocked, suspicious
            $table->string('failure_reason')->nullable(); // Для неудачных попыток: wrong_password, 2fa_failed, etc.
            $table->string('location')->nullable(); // Геолокация (город, страна)
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->boolean('is_suspicious')->default(false); // Подозрительная активность
            $table->text('suspicious_reason')->nullable(); // Причина подозрительности
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('user_id');
            $table->index('email');
            $table->index('ip_address');
            $table->index('status');
            $table->index('is_suspicious');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_history');
    }
};




