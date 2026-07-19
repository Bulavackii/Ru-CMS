<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🔧 Добавление новых полей в messages
 * 
 * - parent_id: для цепочки переписки
 * - is_important: важные сообщения
 * - deleted_at: мягкое удаление (soft delete)
 * - archived_at: архивирование
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('to_user_id')
                ->constrained('messages')->nullOnDelete();
            $table->boolean('is_important')->default(false)->after('is_read');
            $table->timestamp('archived_at')->nullable()->after('is_important');
            $table->softDeletes()->after('archived_at');
        });

        // Добавляем индексы для производительности
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_messages_sender');
            $table->index(['to_user_id', 'is_read', 'created_at'], 'idx_messages_receiver');
            $table->index(['parent_id'], 'idx_messages_parent');
            $table->index(['is_important'], 'idx_messages_important');
            $table->index(['archived_at'], 'idx_messages_archived');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_sender');
            $table->dropIndex('idx_messages_receiver');
            $table->dropIndex('idx_messages_parent');
            $table->dropIndex('idx_messages_important');
            $table->dropIndex('idx_messages_archived');
            
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'is_important', 'archived_at', 'deleted_at']);
        });
    }
};




