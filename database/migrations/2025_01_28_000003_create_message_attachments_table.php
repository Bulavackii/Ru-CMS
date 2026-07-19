<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 📎 Таблица для вложений сообщений
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->string('filename'); // Оригинальное имя файла
            $table->string('path'); // Путь к файлу
            $table->string('mime_type')->nullable(); // MIME тип
            $table->unsignedBigInteger('size')->nullable(); // Размер в байтах
            $table->timestamps();
            
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};




