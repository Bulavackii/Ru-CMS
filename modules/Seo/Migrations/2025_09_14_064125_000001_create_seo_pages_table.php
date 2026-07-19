<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('seo_pages', function (Blueprint $t) {
            $t->id();

            // Привязка к сущности (по желанию модуля)
            $t->string('entity_type')->nullable();          // например: 'news', 'page', 'product'
            $t->unsignedBigInteger('entity_id')->nullable();

            // Ключевой идентификатор страницы (URL-путь)
            $t->string('slug')->unique();                   // один SEO-набор на один URL

            // Он-пейдж
            $t->string('title')->nullable();
            $t->string('h1')->nullable();
            $t->string('description', 512)->nullable();
            $t->string('canonical', 1024)->nullable();      // длинные каноникалы помещаются

            // Роботы
            $t->boolean('robots_index')->default(true);
            $t->boolean('robots_follow')->default(true);

            $t->timestamps();
            $t->softDeletes();

            // Одна SEO-запись на одну сущность (если сущность используется)
            $t->unique(['entity_type', 'entity_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('seo_pages');
    }
};
