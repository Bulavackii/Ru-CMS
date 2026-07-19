<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📊 Таблица для хранения логов ошибок
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('message', 500);
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_agent', 500)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('ip_address');
            $table->index('user_id');
            $table->index(['file', 'line']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};

