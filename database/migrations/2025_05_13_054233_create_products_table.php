<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // Название товара
            $table->text('description')->nullable();            // Описание
            $table->decimal('price', 10, 2)->nullable();         // Цена
            $table->integer('stock')->nullable();                // Остаток на складе
            $table->boolean('is_promo')->default(false);         // Промо-товар
            $table->timestamps();                                // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
