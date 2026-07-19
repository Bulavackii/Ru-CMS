<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🎨 Админ-панель: уведомления и настройки пользователей
 */
return new class extends Migration
{
    public function up(): void
    {
        // Уведомления админки
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('type')->default('info'); // success, error, warning, info
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'read']);
            $table->index('created_at');
        });

        // Настройки пользователей (если таблица users существует)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'settings')) {
                    $table->json('settings')->nullable()->after('is_admin');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
        
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('settings');
            });
        }
    }
};

