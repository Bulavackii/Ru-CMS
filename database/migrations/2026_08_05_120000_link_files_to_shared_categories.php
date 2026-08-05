<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Медиатека переходит на общие категории проекта.
 *
 * У модуля Файлы была своя таблица `file_categories` — параллельная тем
 * категориям, которые владелец заводит в разделе «Категории». Пользоваться ею
 * было нечем: страница управления вела на несуществующую вьюху и отдавала 500,
 * в меню её не было, и за всё время таблица осталась пустой.
 *
 * Причина расхождения — две миграции подряд. Ранняя (`create_files_table`)
 * создаёт `files` со связью на `categories`, а поздняя (`create_files_tables`)
 * заводит `file_categories` и, раз таблица `files` уже есть, уходит в ветку
 * «дописать недостающие колонки» — связь она не переносит. В итоге на обычной
 * установке ключ и так смотрит куда надо, а лишняя таблица просто висит рядом.
 * Но порядок миграций на чужой установке мог оказаться иным, поэтому связь
 * здесь приводится к общей таблице явно.
 *
 * Внешний ключ снимается ПО КОЛОНКЕ, а не по имени: SQLite (на нём гоняются
 * тесты) умеет только так — по имени он бросает исключение, а по колонке
 * перестраивает таблицу целиком.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('files') || ! Schema::hasColumn('files', 'category_id')) {
            return;
        }

        if (! Schema::hasTable('categories')) {
            return;
        }

        // Обнуляем ТОЛЬКО повисшие привязки — те, которым в общей таблице
        // ничего не соответствует. Слепое обнуление всей колонки стёрло бы
        // реальные категории на установках, где связь и так была правильной.
        DB::table('files')
            ->whereNotNull('category_id')
            ->whereNotIn('category_id', fn ($query) => $query->from('categories')->select('id'))
            ->update(['category_id' => null]);

        if ($this->foreignTable() !== 'categories') {
            $this->dropCategoryForeignKey();

            Schema::table('files', function (Blueprint $table) {
                $table->foreign('category_id')
                    ->references('id')->on('categories')
                    ->nullOnDelete();
            });
        }

        Schema::dropIfExists('file_categories');
    }

    /**
     * Возвращает пустую таблицу на место.
     *
     * Связь `files.category_id` при этом не трогается: до отката она вела на
     * `categories` (так её создаёт `create_files_table`), и переводить её на
     * `file_categories` значило бы восстановить не прежнее состояние, а то,
     * которого никогда не было.
     */
    public function down(): void
    {
        if (Schema::hasTable('file_categories')) {
            return;
        }

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

    /** На какую таблицу смотрит связь `files.category_id`, если смотрит вообще. */
    private function foreignTable(): ?string
    {
        foreach (Schema::getForeignKeys('files') as $foreignKey) {
            if (in_array('category_id', $foreignKey['columns'], true)) {
                return $foreignKey['foreign_table'];
            }
        }

        return null;
    }

    /**
     * Снять внешний ключ с `files.category_id`, если он вообще есть.
     *
     * Проверка обязательна: на PostgreSQL удаление несуществующего ключа —
     * ошибка, а таблица `files` старше модуля и в одной из веток миграций
     * создавалась без связи.
     */
    private function dropCategoryForeignKey(): void
    {
        if ($this->foreignTable() === null) {
            return;
        }

        Schema::table('files', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
    }
};
