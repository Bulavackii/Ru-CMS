<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
    if (!Schema::hasTable('redirect_rules')) {
        Schema::create('redirect_rules', function (Blueprint $t) {
            $t->id();
            $t->string('from', 512);
            $t->string('to', 512)->nullable();
            $t->enum('code', ['301','302','410'])->default('301');
            $t->boolean('is_regex')->default(false);
            $t->unsignedInteger('priority')->default(100);
            $t->timestamps();

            $t->index('priority');
            $t->index('is_regex');
            $t->index(['is_regex','from'], 'redirect_rules_is_regex_from_index');
        });
    }
}

    public function down(): void {
        Schema::dropIfExists('redirect_rules');
    }
};
