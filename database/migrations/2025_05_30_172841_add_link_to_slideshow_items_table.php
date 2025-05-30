<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('slideshow_items', function (Blueprint $table) {
            $table->string('link')->nullable()->after('caption');
        });
    }

    public function down(): void
    {
        Schema::table('slideshow_items', function (Blueprint $table) {
            $table->dropColumn('link');
        });
    }
};
