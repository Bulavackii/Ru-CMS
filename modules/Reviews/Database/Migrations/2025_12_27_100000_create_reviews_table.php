<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Связь с сущностью (полиморфная)
            $table->unsignedBigInteger('item_id');
            $table->string('item_type'); // product, service, article и т.д.

            // Автор
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();

            // Оценка и контент
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('title')->nullable();
            $table->text('content');

            // Статус
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Безопасность
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Ответы
            $table->unsignedBigInteger('parent_id')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['item_id', 'item_type']);
            $table->index('user_id');
            $table->index('status');
            $table->index('parent_id');
            $table->index('rating');
            $table->index('created_at');

            // Внешние ключи
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('reviews')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
