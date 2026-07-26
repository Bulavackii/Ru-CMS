<?php

namespace App\Support;

use App\Models\ContentTranslation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

/**
 * 🌐 Переводы полей модели на другие языки.
 *
 * Подключение к модели:
 *
 *     use App\Support\HasContentTranslations;
 *
 *     class News extends Model
 *     {
 *         use HasContentTranslations;
 *
 *         public array $translatable = ['title', 'content'];
 *     }
 *
 * Дальше `$news->t('title')` отдаёт заголовок на текущем языке, а если
 * перевода нет — оригинал из самой записи. Пустых мест на сайте не возникает.
 *
 * Оригинал считается русским (locale приложения по умолчанию): для него
 * переводы не хранятся и не запрашиваются.
 */
trait HasContentTranslations
{
    /** Кеш переводов в пределах запроса: [locale => [field => value]] */
    protected array $translationMemo = [];

    public static function bootHasContentTranslations(): void
    {
        // Правка записи или её удаление обесценивают кеш переводов
        static::saved(fn ($model) => $model->flushTranslationCache());
        static::deleted(function ($model) {
            $model->flushTranslationCache();

            // Мягкое удаление переводы не трогает — они понадобятся при restore
            if (! method_exists($model, 'trashed') || ! $model->trashed()) {
                $model->translations()->delete();
            }
        });
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(ContentTranslation::class, 'translatable');
    }

    /** Поля, которые можно переводить */
    public function translatableFields(): array
    {
        return property_exists($this, 'translatable') ? (array) $this->translatable : [];
    }

    /**
     * Язык оригинала — тот, на котором заполнена сама запись.
     *
     * ⚠️ Берём config('app.content_locale'), а НЕ config('app.locale'):
     * последний Laravel переписывает при app()->setLocale(), поэтому «язык
     * оригинала» всегда совпадал бы с языком посетителя и переводы никогда
     * бы не применялись.
     */
    public static function originalLocale(): string
    {
        return (string) config('app.content_locale', 'ru');
    }

    /**
     * Значение поля на текущем (или заданном) языке.
     * Нет перевода — возвращается оригинал, а не пустая строка.
     */
    public function t(string $field, ?string $locale = null)
    {
        $original = $this->getAttribute($field);
        $locale = $locale ?: app()->getLocale();

        if ($locale === static::originalLocale() || ! in_array($field, $this->translatableFields(), true)) {
            return $original;
        }

        $value = $this->translationsFor($locale)[$field] ?? null;

        return ($value === null || trim((string) $value) === '') ? $original : $value;
    }

    /** Все переводы записи на язык (кешируются) */
    public function translationsFor(string $locale): array
    {
        if (isset($this->translationMemo[$locale])) {
            return $this->translationMemo[$locale];
        }

        if (! $this->exists) {
            return [];
        }

        $key = $this->translationCacheKey($locale);

        try {
            $values = Cache::remember($key, 3600, fn () => $this->translations()
                ->where('locale', $locale)
                ->pluck('value', 'field')
                ->all());
        } catch (\Throwable $e) {
            // Сайт не должен падать из-за переводов (нет таблицы, нет БД)
            $values = [];
        }

        return $this->translationMemo[$locale] = $values;
    }

    /**
     * Сохранить переводы: ['en' => ['title' => '...'], 'de' => [...]].
     * Пустое значение удаляет перевод — поле снова возьмёт оригинал.
     */
    public function saveTranslations(array $input): void
    {
        $allowed = $this->translatableFields();

        foreach ($input as $locale => $fields) {
            if (! is_array($fields) || $locale === static::originalLocale()) {
                continue;
            }

            foreach ($fields as $field => $value) {
                if (! in_array($field, $allowed, true)) {
                    continue;
                }

                $value = is_string($value) ? trim($value) : $value;

                if ($value === null || $value === '') {
                    $this->translations()->where('locale', $locale)->where('field', $field)->delete();
                    continue;
                }

                $this->translations()->updateOrCreate(
                    ['locale' => $locale, 'field' => $field],
                    ['value' => $value]
                );
            }
        }

        $this->flushTranslationCache();
    }

    /** На сколько языков запись переведена (хотя бы одно поле) */
    public function translatedLocales(): array
    {
        if (! $this->exists) {
            return [];
        }

        try {
            return $this->translations()
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->distinct()
                ->pluck('locale')
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function flushTranslationCache(): void
    {
        $this->translationMemo = [];

        foreach (available_locales() as $locale) {
            Cache::forget($this->translationCacheKey($locale));
        }
    }

    protected function translationCacheKey(string $locale): string
    {
        return 'content_tr_' . md5(static::class) . '_' . $this->getKey() . '_' . $locale;
    }
}
