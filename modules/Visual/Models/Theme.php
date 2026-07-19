<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Theme extends Model
{
    protected $table = 'visual_themes';
    protected $fillable = ['slug','title','tokens','config','is_default'];
    
    protected function casts(): array
    {
        return [
            'tokens' => 'array',
            'config' => 'array',
            'is_default' => 'boolean',
        ];
    }

    /**
     * 🔄 Инвалидация кэша при изменениях
     */
    protected static function booted()
    {
        static::saved(function ($theme) {
            Cache::forget('active_theme');
            Cache::forget('active_theme_css');
            if ($theme->is_default) {
                Cache::forever('active_theme_id', $theme->id);
            }
        });

        static::deleted(function ($theme) {
            Cache::forget('active_theme');
            Cache::forget('active_theme_css');
            if (Cache::get('active_theme_id') == $theme->id) {
                Cache::forget('active_theme_id');
            }
        });
    }

    /**
     * 📦 Получить активную тему (с кэшированием)
     */
    public static function getActive()
    {
        return Cache::remember('active_theme', 3600, function () {
            $themeId = Cache::get('active_theme_id');
            if ($themeId) {
                return static::find($themeId);
            }
            return static::where('is_default', true)->first();
        });
    }
}
