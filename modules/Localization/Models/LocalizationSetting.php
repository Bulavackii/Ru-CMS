<?php

namespace Modules\Localization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalizationSetting extends Model
{
    use SoftDeletes;

    protected $table = 'localization_settings';

    protected $fillable = [
        'country_id',
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Получить все настройки для страны в виде массива
     */
    public static function getAllForCountry(int $countryId): array
    {
        return cache()->remember(
            "localization_settings_country_{$countryId}",
            3600,
            function () use ($countryId) {
                $settings = static::where('country_id', $countryId)->get();
                $result = [];

                foreach ($settings as $setting) {
                    $value = $setting->getValue();
                    $result[$setting->key] = $value;
                }

                return $result;
            }
        );
    }

    /**
     * Получить конкретную настройку для страны
     */
    public static function getForCountry(int $countryId, string $key, $default = null)
    {
        $setting = static::where('country_id', $countryId)
            ->where('key', $key)
            ->first();

        if (!$setting) {
            return $default;
        }

        return $setting->getValue();
    }

    /**
     * Установить настройку для страны
     */
    public static function set(int $countryId, string $key, $value, string $type = 'string', string $group = 'general', string $description = ''): self
    {
        $setting = static::where('country_id', $countryId)
            ->where('key', $key)
            ->firstOrNew([
                'country_id' => $countryId,
                'key' => $key,
            ]);

        $setting->value = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        $setting->type = $type;
        $setting->group = $group;
        $setting->description = $description;
        $setting->save();

        // Очистить кеш
        cache()->forget("localization_settings_country_{$countryId}");

        return $setting;
    }

    /**
     * Получить значение с учетом типа
     */
    public function getValue()
    {
        if (!$this->value) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => is_numeric($this->value) ? (int)$this->value : null,
            'float' => is_numeric($this->value) ? (float)$this->value : null,
            'json', 'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Get timezone offset in hours
     */
    public function getTimezoneOffset(): string
    {
        $timezone = $this->getValue() ?? 'UTC';
        
        try {
            $timezoneObj = new \DateTimeZone($timezone);
            $date = new \DateTime('now', $timezoneObj);
            $offset = $timezoneObj->getOffset($date);

            $hours = floor($offset / 3600);
            $minutes = abs(($offset % 3600) / 60);

            return sprintf('%+d:%02d', $hours, $minutes);
        } catch (\Exception $e) {
            return '+0:00';
        }
    }

    /**
     * Convert date to timezone
     */
    public function convertTimezone($datetime, $toTimezone = null): \DateTime
    {
        $fromTz = $this->getValue() ?? 'UTC';
        $toTz = $toTimezone ?? $fromTz;

        try {
            $date = new \DateTime($datetime, new \DateTimeZone($fromTz));
            $date->setTimezone(new \DateTimeZone($toTz));
            return $date;
        } catch (\Exception $e) {
            return new \DateTime($datetime);
        }
    }
}
