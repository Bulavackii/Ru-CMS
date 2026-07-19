<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accessibility_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->boolean('enable_font_size')->default(true);
            $table->boolean('enable_speech')->default(true);
            $table->boolean('enable_contrast')->default(true);
            $table->boolean('enable_background')->default(true);
            $table->boolean('enable_highlight_links')->default(true);

            // 🔽 Новые поля
            $table->boolean('enable_reading_mask')->default(false);
            $table->boolean('enable_read_mode')->default(false);
            $table->boolean('enable_text_spacing')->default(false);
            $table->boolean('enable_dyslexia_font')->default(false);
            $table->boolean('enable_multilingual_support')->default(false);
            $table->boolean('enable_bw_mode')->default(false);
            $table->boolean('enable_colorblind_mode')->default(false);
            $table->boolean('enable_sepia_mode')->default(false);
            $table->boolean('enable_selected_text_speech')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessibility_settings');
    }
};
