<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('message');
            $table->enum('type', ['text', 'html', 'cookie']);
            $table->enum('target', ['all', 'admin', 'user']);
            $table->enum('position', ['top', 'bottom', 'fullscreen']);
            $table->integer('duration')->nullable();
            $table->string('icon')->nullable();
            $table->string('route_filter')->nullable();
            $table->string('cookie_key')->nullable();
            $table->boolean('enabled')->default(true);
            $table->string('bg_color', 20)->nullable();
            $table->string('text_color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('notifications');
    }
};
