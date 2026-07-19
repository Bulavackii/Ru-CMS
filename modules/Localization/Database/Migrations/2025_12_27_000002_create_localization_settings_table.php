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
        Schema::create('localization_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->string('context')->default('frontend'); // frontend, admin, api, etc.
            $table->string('date_format')->default('d.m.Y');
            $table->string('time_format')->default('H:i');
            $table->string('decimal_separator')->default('.');
            $table->string('thousands_separator')->default(' ');
            $table->integer('decimal_places')->default(2);
            $table->string('currency_code')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->string('locale')->default('ru_RU');
            $table->string('timezone')->default('UTC');
            $table->boolean('active')->default(true);
            $table->json('translations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_id', 'context', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localization_settings');
    }
};
