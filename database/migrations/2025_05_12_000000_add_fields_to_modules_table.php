<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->timestamp('installed_at')->nullable();
            $table->unsignedInteger('priority')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn(['installed_at', 'priority']);
        });
    }
};
