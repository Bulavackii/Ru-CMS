<?php

namespace Modules\Accessibility\Models;

use Illuminate\Database\Eloquent\Model;

class AccessibilitySetting extends Model
{
    protected $table = 'accessibility_settings';

    protected $fillable = [
        'enabled',
        'enable_font_size',
        'enable_speech',
        'enable_contrast',
        'enable_background',
        'enable_highlight_links',
        'enable_reading_mask',
        'enable_read_mode',
        'enable_text_spacing',
        'enable_dyslexia_font',
        'enable_multilingual_support',
        'enable_bw_mode',
        'enable_colorblind_mode',
        'enable_sepia_mode',
        'enable_selected_text_speech',
    ];

    public $timestamps = true;

    public static function settings(): self
    {
        return static::firstOrCreate([], [
            'enabled' => true,
            'enable_font_size' => true,
            'enable_speech' => true,
            'enable_contrast' => true,
            'enable_background' => true,
            'enable_highlight_links' => true,
            'enable_reading_mask' => false,
            'enable_read_mode' => false,
            'enable_text_spacing' => false,
            'enable_dyslexia_font' => false,
            'enable_multilingual_support' => false,
            'enable_bw_mode' => false,
            'enable_colorblind_mode' => false,
            'enable_sepia_mode' => false,
            'enable_selected_text_speech' => false,
        ]);
    }
}
