<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // под MySQL
        DB::statement("ALTER TABLE `visual_fragments` MODIFY `type` VARCHAR(100) NOT NULL DEFAULT 'html'");
    }

    public function down(): void
    {
        // вернём без дефолта (если надо)
        DB::statement("ALTER TABLE `visual_fragments` MODIFY `type` VARCHAR(100) NOT NULL");
    }
};
