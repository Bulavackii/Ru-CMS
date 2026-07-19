<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💬 Комментарии - таблицы для системы комментариев
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // App\Models\News, App\Models\Page
            $table->unsignedBigInteger('model_id');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('author_name')->nullable(); // Для гостевых комментариев
            $table->string('author_email')->nullable();
            $table->text('content');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'spam', 'trash'])->default('pending');
            $table->integer('likes')->default(0);
            $table->integer('dislikes')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('parent_id');
            $table->index('status');
            $table->index('user_id');
            $table->index('created_at');
        });

        // Лайки/дизлайки комментариев
        Schema::create('comment_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->enum('vote', ['like', 'dislike']);
            $table->timestamps();

            $table->unique(['comment_id', 'user_id', 'ip_address']);
            $table->index('comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_votes');
        Schema::dropIfExists('comments');
    }
};

