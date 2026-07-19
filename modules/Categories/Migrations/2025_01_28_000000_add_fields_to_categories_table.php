<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Slug для SEO-friendly URL
            $table->string('slug')->nullable()->unique()->after('title');
            
            // Описание категории
            $table->text('description')->nullable()->after('slug');
            
            // Иконка категории
            $table->string('icon')->nullable()->after('description');
            
            // Иерархия категорий
            $table->foreignId('parent_id')->nullable()->after('icon')
                ->constrained('categories')->onDelete('cascade');
            
            // Порядок сортировки
            $table->integer('sort_order')->default(0)->after('parent_id');
            
            // Активность категории
            $table->boolean('is_active')->default(true)->after('sort_order');
            
            // Soft deletes
            $table->softDeletes()->after('updated_at');
            
            // Индексы для производительности
            $table->index('slug');
            $table->index('type');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['slug']);
            $table->dropIndex(['type']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['sort_order']);
            
            $table->dropColumn([
                'slug',
                'description',
                'icon',
                'parent_id',
                'sort_order',
                'is_active',
                'deleted_at',
            ]);
        });
    }
};




