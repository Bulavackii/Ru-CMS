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
        Schema::create('slideshow_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slideshow_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->enum('media_type', ['image', 'video']);
            $table->integer('position')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slideshow_items');
    }
};
