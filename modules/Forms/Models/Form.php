<?php

namespace Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 📝 Форма, собранная в конструкторе.
 *
 * Выводится на сайте шорткодом [form slug="…"] в любом материале либо вызовом
 * form_render() из шаблона. Набор полей хранится документом (JSON), потому что
 * правится он всегда целиком.
 */
class Form extends Model
{
    protected $table = 'forms';

    protected $fillable = [
        'title', 'slug', 'description', 'fields', 'settings', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fields'    => 'array',
            'settings'  => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Типы полей, которые умеет собирать конструктор и проверять сервер.
     *
     * Список — единый источник для формы конструктора, отрисовки и проверки:
     * тип, которого здесь нет, не будет ни предложен, ни принят.
     */
    public const FIELD_TYPES = [
        'text', 'textarea', 'email', 'tel', 'number', 'url', 'date', 'time',
        'select', 'radio', 'checkbox', 'checkboxes', 'file', 'hidden',
        'heading', 'paragraph', 'consent',
    ];

    /** Поля, у которых есть список вариантов. */
    public const TYPES_WITH_OPTIONS = ['select', 'radio', 'checkboxes'];

    /** Поля, которые ничего не спрашивают — это разметка, а не ввод. */
    public const DECORATIVE_TYPES = ['heading', 'paragraph'];

    /**
     * Типы, которые встают в половину строки.
     *
     * Ширина не спрашивается у владельца, а считается: телефон, дата и число
     * рядом читаются лучше, чем растянутые во всю строку, а сообщение или
     * список вариантов — наоборот. Список живёт здесь, потому что нужен и
     * конструктору, и сидеру: две копии разошлись бы, и формы по умолчанию
     * выглядели бы иначе, чем собранные руками.
     */
    public const HALF_WIDTH_TYPES = ['text', 'email', 'tel', 'number', 'url', 'date', 'time', 'select'];

    /** Ширина поля по его типу. */
    public static function widthFor(string $type): string
    {
        return in_array($type, self::HALF_WIDTH_TYPES, true) ? 'half' : 'full';
    }

    private const CACHE_KEY = 'forms_active_list';

    protected static function booted(): void
    {
        static::saving(function (self $form) {
            $form->slug = static::uniqueSlug($form->slug ?: $form->title, $form->id);
        });

        // Список форм показывается в редакторе материалов на каждой странице —
        // держим его в кеше и сбрасываем на любом изменении.
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /** Активные формы — для выпадающих списков в редакторах. */
    public static function activeList()
    {
        return Cache::remember(self::CACHE_KEY, 3600, fn () => static::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug']));
    }

    /**
     * Форма по слагу. null — если её нет или она выключена.
     *
     * Материал с шорткодом на удалённую форму должен открываться как обычно,
     * поэтому здесь именно null, а не исключение.
     */
    public static function findActive(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Уникальный слаг.
     *
     * Str::slug на кириллице даёт транслитерацию, но у формы с названием из
     * одних эмодзи или знаков результат пустой — тогда берём отметку времени,
     * иначе слаг был бы пустой строкой и шорткод перестал бы работать.
     */
    public static function uniqueSlug(?string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $source) ?: 'form-' . now()->format('YmdHis');
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    /** Шорткод для вставки в материал. */
    public function shortcode(): string
    {
        return '[form slug="' . $this->slug . '"]';
    }

    /** Вызов из Blade-шаблона темы. */
    public function bladeSnippet(): string
    {
        return "{!! form_render('{$this->slug}') !!}";
    }

    /**
     * Имя класса иконки, пригодное для подстановки в атрибут class.
     *
     * Пропускаем только то, из чего состоят имена Font Awesome: латиница,
     * цифры, дефис и пробел между двумя классами. Без этого в class можно было
     * бы вписать что угодно — включая закрывающую кавычку и свой атрибут.
     */
    public static function safeIcon(mixed $icon): string
    {
        $icon = trim((string) $icon);

        return preg_match('~^[a-z0-9 \-]{1,40}$~i', $icon) ? $icon : '';
    }

    /** Иконка самой формы. */
    public function icon(): string
    {
        return self::safeIcon($this->setting('icon'));
    }

    /** Настройка формы со значением по умолчанию. */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Поля с гарантированной структурой.
     *
     * Конструктор пишет их сам, но форма могла быть заведена командой-сидером
     * или отредактирована в базе руками — отрисовка не должна падать на
     * отсутствующем ключе.
     *
     * @return array<int, array<string, mixed>>
     */
    public function normalizedFields(): array
    {
        $fields = [];

        foreach ((array) $this->fields as $index => $field) {
            if (! is_array($field)) {
                continue;
            }

            $type = in_array($field['type'] ?? '', self::FIELD_TYPES, true) ? $field['type'] : 'text';

            $fields[] = [
                'type'        => $type,
                'name'        => (string) ($field['name'] ?? 'field_' . ($index + 1)),
                // Иконка поля — класс Font Awesome. Чистится ЗДЕСЬ, а не
                // только на приёме из формы: запись могла попасть в базу от
                // сидера или руками, а значение уходит прямо в атрибут class.
                'icon'        => self::safeIcon($field['icon'] ?? ''),
                'label'       => (string) ($field['label'] ?? ''),
                'placeholder' => (string) ($field['placeholder'] ?? ''),
                'hint'        => (string) ($field['hint'] ?? ''),
                'value'       => (string) ($field['value'] ?? ''),
                'required'    => (bool) ($field['required'] ?? false),
                'width'       => in_array($field['width'] ?? 'full', ['full', 'half'], true) ? $field['width'] : 'full',
                'options'     => array_values(array_filter(
                    array_map('strval', (array) ($field['options'] ?? [])),
                    fn ($option) => $option !== ''
                )),
            ];
        }

        return $fields;
    }
}
