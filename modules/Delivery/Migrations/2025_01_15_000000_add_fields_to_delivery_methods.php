<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_methods', function (Blueprint $table) {
            $table->decimal('free_delivery_threshold', 10, 2)->nullable()->after('regions');
            $table->unsignedInteger('sort_order')->default(0)->after('free_delivery_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_methods', function (Blueprint $table) {
            $table->dropColumn(['free_delivery_threshold', 'sort_order']);
        });
    }
};





