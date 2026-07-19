<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Schema::change() вместо сырого "MySQL-only" ALTER ... MODIFY —
        // тот падал под SQLite (используется в тестах).
        Schema::table('visual_fragments', function (Blueprint $table) {
            $table->string('type', 100)->default('html')->change();
        });
    }

    public function down(): void
    {
        Schema::table('visual_fragments', function (Blueprint $table) {
            $table->string('type', 100)->change();
        });
    }
};
