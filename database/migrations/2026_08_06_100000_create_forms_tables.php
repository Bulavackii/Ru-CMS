<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Модуль «Формы»: сами формы и присланные заявки.
 *
 * Поля формы лежат в JSON, а не отдельной таблицей. Причина: набор полей —
 * это единый документ, который правится целиком в конструкторе (перетащил,
 * переименовал, поменял порядок и сохранил). Отдельная таблица заставляла бы
 * на каждое сохранение сверять, что удалить, что вставить, а что подвинуть, —
 * и всё это ради данных, которые никогда не запрашиваются по одному полю.
 *
 * Ответы посетителей хранятся ВСЕГДА, даже когда настроена отправка на почту:
 * письмо может не уйти (нет SMTP, упал релей, попало в спам), и тогда заявка
 * просто исчезнет. База — единственное место, где она точно останется.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('forms')) {
            Schema::create('forms', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('description')->nullable();

                // Поля конструктора и настройки поведения: кнопка, письмо,
                // защита, текст благодарности.
                $table->json('fields')->nullable();
                $table->json('settings')->nullable();

                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('submissions_count')->default(0);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('form_submissions')) {
            Schema::create('form_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();

                // Ответы целиком: набор полей у формы меняется, и колонки под
                // них завести нельзя — вчерашняя заявка должна открываться
                // такой, какой её прислали, даже если поле потом убрали.
                $table->json('data');

                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->string('page')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->index(['form_id', 'created_at']);
                $table->index('is_read');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('forms');
    }
};
