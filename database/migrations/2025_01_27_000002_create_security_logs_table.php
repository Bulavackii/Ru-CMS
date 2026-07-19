<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔒 Логи безопасности
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // login_attempt, blocked_ip, sql_injection, xss_attempt
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action')->nullable(); // attempted_action
            $table->text('details')->nullable(); // JSON
            $table->boolean('blocked')->default(false);
            $table->timestamps();
            
            $table->index(['type', 'created_at']);
            $table->index('ip_address');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};

