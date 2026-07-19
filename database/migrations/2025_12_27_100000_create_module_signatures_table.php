<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('module_signatures', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module_name')->unique();
            $table->text('signature')->nullable();
            $table->text('public_key')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('hash_algorithm')->default('sha256');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_signatures');
    }
};
