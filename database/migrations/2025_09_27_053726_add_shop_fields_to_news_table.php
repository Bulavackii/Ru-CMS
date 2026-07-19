<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            // decimal(12,2) под цену; сделай свои размеры при желании
            if (!Schema::hasColumn('news', 'price')) {
                $table->decimal('price', 12, 2)->nullable()->after('meta_header');
            }
            if (!Schema::hasColumn('news', 'stock')) {
                $table->integer('stock')->nullable()->after('price');
            }
            if (!Schema::hasColumn('news', 'is_promo')) {
                $table->boolean('is_promo')->default(false)->after('stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'is_promo')) $table->dropColumn('is_promo');
            if (Schema::hasColumn('news', 'stock'))    $table->dropColumn('stock');
            if (Schema::hasColumn('news', 'price'))    $table->dropColumn('price');
        });
    }
};
