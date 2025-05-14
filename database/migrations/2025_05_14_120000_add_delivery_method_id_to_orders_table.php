<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_method_id')) {
                $table->unsignedBigInteger('delivery_method_id')->nullable()->after('payment_method_id');
                $table->foreign('delivery_method_id')->references('id')->on('delivery_methods')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_method_id')) {
                $table->dropForeign(['delivery_method_id']);
                $table->dropColumn('delivery_method_id');
            }
        });
    }
};
