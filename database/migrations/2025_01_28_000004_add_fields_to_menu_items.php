<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔧 Добавление новых полей в menu_items
 * 
 * - active: активность пункта меню
 * - icon: иконка для пункта
 * - css_class: кастомные CSS классы
 * - target: target атрибут ссылки (_self, _blank)
 * - rel: rel атрибут ссылки (nofollow, noopener и т.д.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('order');
            $table->string('icon')->nullable()->after('active');
            $table->string('css_class')->nullable()->after('icon');
            $table->enum('target', ['_self', '_blank'])->nullable()->after('css_class');
            $table->string('rel')->nullable()->after('target');
        });

        // Добавляем индексы для производительности
        Schema::table('menu_items', function (Blueprint $table) {
            $table->index(['menu_id', 'parent_id', 'order'], 'idx_menu_items_tree');
            $table->index(['type', 'linked_id'], 'idx_menu_items_linked');
            $table->index('active', 'idx_menu_items_active');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex('idx_menu_items_tree');
            $table->dropIndex('idx_menu_items_linked');
            $table->dropIndex('idx_menu_items_active');
            
            $table->dropColumn(['active', 'icon', 'css_class', 'target', 'rel']);
        });
    }
};


