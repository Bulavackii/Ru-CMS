<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('template');
            $table->integer('stock')->nullable()->after('price');
            $table->boolean('is_promo')->default(false)->after('stock');
        });
    }

    public function down()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['price', 'stock', 'is_promo']);
        });
    }
};
