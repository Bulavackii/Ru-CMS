<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔄 Версионирование контента - история изменений
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_versions', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // App\Models\News, App\Models\Page
            $table->unsignedBigInteger('model_id');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->json('data'); // Полные данные модели на момент версии
            $table->text('changes')->nullable(); // Описание изменений
            $table->string('version_number')->default('1.0.0');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('is_current');
            $table->index('created_at');
        });

        // Автосохраненные черновики
        Schema::create('content_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id')->nullable(); // null для новых записей
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('data');
            $table->string('key')->unique(); // Уникальный ключ для восстановления
            $table->timestamp('saved_at');
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('saved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_drafts');
        Schema::dropIfExists('content_versions');
    }
};

