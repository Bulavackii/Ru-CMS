<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visual_fragments', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('type')->index();
            $t->json('schema')->nullable();
            $t->json('data')->nullable();
            $t->longText('html_cached')->nullable();
            $t->text('css_inline')->nullable();
            $t->boolean('is_active')->default(true);
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visual_fragments');
    }
};
