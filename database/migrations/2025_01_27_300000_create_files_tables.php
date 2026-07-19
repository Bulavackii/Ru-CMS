<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📁 Файлы - таблицы для медиа-библиотеки
 */
return new class extends Migration
{
    public function up(): void
    {
        // Категории файлов
        if (!Schema::hasTable('file_categories')) {
            Schema::create('file_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('file_categories')->onDelete('cascade');
                $table->timestamps();

                $table->index('slug');
                $table->index('parent_id');
            });
        }

        // Файлы - создаем или обновляем структуру
        if (!Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Имя файла на диске
                $table->string('original_name'); // Оригинальное имя
                $table->string('path'); // Путь относительно storage
                $table->string('mime_type');
                $table->unsignedBigInteger('size'); // Размер в байтах
                $table->unsignedInteger('width')->nullable(); // Ширина (для изображений)
                $table->unsignedInteger('height')->nullable(); // Высота (для изображений)
                $table->foreignId('category_id')->nullable()->constrained('file_categories')->onDelete('set null');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('alt_text')->nullable(); // Alt текст для изображений
                $table->text('description')->nullable();
                $table->json('tags')->nullable(); // Теги для поиска
                $table->timestamps();

                $table->index('mime_type');
                $table->index('category_id');
                $table->index('user_id');
                $table->index('created_at');
            });
        } else {
            // Обновляем существующую таблицу files - добавляем недостающие поля
            Schema::table('files', function (Blueprint $table) {
                // Добавляем mime_type, если есть только mime (или если нет ни того, ни другого)
                if (!Schema::hasColumn('files', 'mime_type')) {
                    $table->string('mime_type')->nullable()->after('path');
                }

                // Добавляем недостающие поля
                if (!Schema::hasColumn('files', 'original_name')) {
                    $table->string('original_name')->nullable()->after('name');
                }
                if (!Schema::hasColumn('files', 'width')) {
                    $table->unsignedInteger('width')->nullable()->after('size');
                }
                if (!Schema::hasColumn('files', 'height')) {
                    $table->unsignedInteger('height')->nullable()->after('width');
                }
                if (!Schema::hasColumn('files', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->after('category_id')->constrained()->onDelete('set null');
                }
                if (!Schema::hasColumn('files', 'alt_text')) {
                    $table->string('alt_text')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('files', 'description')) {
                    $table->text('description')->nullable()->after('alt_text');
                }
                if (!Schema::hasColumn('files', 'tags')) {
                    $table->json('tags')->nullable()->after('description');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
        Schema::dropIfExists('file_categories');
    }
};

