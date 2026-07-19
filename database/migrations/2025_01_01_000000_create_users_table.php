<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('telegram', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('vk')->nullable();
            $table->string('zip', 20)->nullable();
            $table->boolean('is_company')->default(false);
            $table->string('company_name')->nullable();
            $table->string('inn', 20)->nullable();
            $table->string('ogrn', 20)->nullable();
            $table->string('ceo')->nullable();
            $table->string('address_legal')->nullable();
            $table->string('address_actual')->nullable();
            $table->string('okato', 20)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};
