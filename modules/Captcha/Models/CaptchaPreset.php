<?php

namespace Modules\Captcha\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 🧩 Сохранённая сборка каптчи.
 *
 * Собирается мышью в конструкторе (/admin/captcha) и вставляется в материалы
 * шорткодом [captcha preset="slug"] либо в шаблон вызовом captcha_preset().
 *
 * Тип хранится вместе с параметрами не для красоты: verify() сверяет тип, и
 * если пресет «слайдер» проверить как image, не пройдёт никто.
 */
class CaptchaPreset extends Model
{
    protected $table = 'captcha_presets';

    protected $fillable = ['name', 'slug', 'type', 'options', 'is_active'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** Типы, которые реально умеет CaptchaService. */
    public const TYPES = ['image', 'slider', 'math', 'question'];

    protected static function booted(): void
    {
        static::saving(function (self $preset) {
            $preset->slug = static::uniqueSlug($preset->slug ?: $preset->name, $preset->id);
        });

        // Список пресетов показывается в редакторах материалов на каждой
        // странице — держим его в кеше и сбрасываем на любом изменении
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    private const CACHE_KEY = 'captcha_presets_active';

    /** Активные пресеты — для выпадающих списков в редакторах. */
    public static function activeList()
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return static::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'type']);
        });
    }

    /** Пресет по слагу. null — если его нет или он выключен. */
    public static function findActive(string $slug): ?self
    {
        return static::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /** Шорткод для вставки в текст материала. */
    public function shortcode(): string
    {
        return '[captcha preset="' . $this->slug . '"]';
    }

    /** Вызов для вставки прямо в Blade-шаблон. */
    public function bladeSnippet(): string
    {
        return "{!! captcha_preset('{$this->slug}') !!}";
    }

    /** Что дописать в контроллер, чтобы ответ реально проверялся. */
    public function verifySnippet(): string
    {
        return "\$request->validate([\n    'captcha' => 'required|captcha',\n]);";
    }

    /**
     * Уникальный слаг. Кириллица через Str::slug превращается в пустую
     * строку, поэтому для неё берём транслитерацию, а если и она пуста —
     * генерируем читаемый запасной вариант.
     */
    private static function uniqueSlug(?string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $source);

        if ($base === '') {
            $base = Str::slug(Str::ascii((string) $source));
        }

        if ($base === '') {
            $base = 'captcha-' . Str::lower(Str::random(6));
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
