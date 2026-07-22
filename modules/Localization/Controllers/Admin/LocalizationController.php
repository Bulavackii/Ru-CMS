<?php

namespace Modules\Localization\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Localization\Models\Country;
use Modules\Localization\Models\LocalizationSetting;
use Modules\Localization\Services\LocalizationService;
use Illuminate\Support\Facades\Validator;

class LocalizationController extends Controller
{
    protected $localizationService;

    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * Get all countries with their settings
     */
    public function index()
    {
        $countries = Country::withCount('settings')
            ->orderBy('name')
            ->get();
        
        $stats = $this->localizationService->getStats();

        return view('Localization::admin.index', compact('countries', 'stats'));
    }

    /**
     * Show form for creating a new country
     */
    public function create()
    {
        return view('Localization::admin.create', [
            'presets' => config('localization.preset_countries', []),
            'dateFormats' => $this->dateFormatOptions(),
            'timeFormats' => $this->timeFormatOptions(),
            'decimalSeparators' => $this->decimalSeparatorOptions(),
            'thousandsSeparators' => $this->thousandsSeparatorOptions(),
        ]);
    }

    /**
     * Show form for editing a country
     */
    public function edit(string $code)
    {
        $country = Country::where('code', $code)->firstOrFail();

        return view('Localization::admin.edit', [
            'country' => $country,
            'settings' => LocalizationSetting::getAllForCountry($country->id),
            'dateFormats' => $this->dateFormatOptions(),
            'timeFormats' => $this->timeFormatOptions(),
            'decimalSeparators' => $this->decimalSeparatorOptions(),
            'thousandsSeparators' => $this->thousandsSeparatorOptions(),
        ]);
    }

    /**
     * Show settings for a country
     */
    public function settings(string $code)
    {
        $country = Country::where('code', $code)->firstOrFail();

        // Плоский массив key => value (как отдаёт API countrySettings) —
        // именно в таком виде его ждёт вьюха (экспорт в JSON, быстрые настройки
        // по ключу и т.д.). Группировка по полю 'group' тут не нужна.
        $settings = LocalizationSetting::getAllForCountry($country->id);

        return view('Localization::admin.settings', [
            'country' => $country,
            'settings' => $settings,
            'types' => $this->settingTypeOptions(),
            'groups' => $this->settingGroupOptions(),
        ]);
    }

    /**
     * Варианты форматов даты для выпадающего списка в формах создания/редактирования страны
     */
    private function dateFormatOptions(): array
    {
        return [
            'd.m.Y' => '31.12.2026',
            'Y-m-d' => '2026-12-31',
            'd/m/Y' => '31/12/2026',
            'm/d/Y' => '12/31/2026',
            'd F Y' => '31 декабря 2026',
        ];
    }

    /**
     * Варианты форматов времени
     */
    private function timeFormatOptions(): array
    {
        return [
            'H:i' => '14:30',
            'H:i:s' => '14:30:00',
            'h:i A' => '02:30 PM',
        ];
    }

    /**
     * Варианты разделителя дробной части числа
     */
    private function decimalSeparatorOptions(): array
    {
        return [
            ',' => 'Запятая — 1,5',
            '.' => 'Точка — 1.5',
        ];
    }

    /**
     * Варианты разделителя разрядов числа
     */
    private function thousandsSeparatorOptions(): array
    {
        return [
            ' ' => 'Пробел — 1 000',
            ',' => 'Запятая — 1,000',
            '.' => 'Точка — 1.000',
            '' => 'Без разделителя — 1000',
        ];
    }

    /**
     * Типы значений для произвольных настроек локализации
     */
    private function settingTypeOptions(): array
    {
        return [
            'string' => 'Строка',
            'number' => 'Число',
            'boolean' => 'Логическое (да/нет)',
            'json' => 'JSON',
            'array' => 'Массив',
        ];
    }

    /**
     * Группы произвольных настроек локализации
     */
    private function settingGroupOptions(): array
    {
        return [
            'general' => 'Общие',
            'date' => 'Дата и время',
            'currency' => 'Валюта',
            'translation' => 'Переводы',
        ];
    }

    /**
     * Get settings for a specific country
     */
    public function show(string $countryCode): JsonResponse
    {
        $country = Country::with('settings')->where('code', $countryCode)->first();

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $country,
        ]);
    }

    /**
     * Store a newly created country
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:2|unique:countries,code',
            'name' => 'required|string|max:255',
            'native_name' => 'nullable|string|max:255',
            'flag' => 'nullable|string|max:10',
            'currency_code' => 'required|string|size:3',
            'currency_symbol' => 'nullable|string|max:10',
            'locale' => 'required|string|max:20',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:50',
            'time_format' => 'required|string|max:50',
            'decimal_separator' => 'required|string|max:5',
            'thousands_separator' => 'required|string|max:5',
            'decimal_places' => 'required|integer|min:0|max:10',
            'active' => 'boolean',
        ]);

        $country = Country::create($validated);

        return redirect()
            ->route('admin.localization.index')
            ->with('success', "Страна {$country->name} успешно создана");
    }

    /**
     * Update country
     */
    public function update(Request $request, string $code)
    {
        $country = Country::where('code', $code)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'native_name' => 'nullable|string|max:255',
            'flag' => 'nullable|string|max:10',
            'currency_code' => 'required|string|size:3',
            'currency_symbol' => 'nullable|string|max:10',
            'locale' => 'required|string|max:20',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:50',
            'time_format' => 'required|string|max:50',
            'decimal_separator' => 'required|string|max:5',
            'thousands_separator' => 'required|string|max:5',
            'decimal_places' => 'required|integer|min:0|max:10',
            'active' => 'boolean',
        ]);

        $country->update($validated);

        return redirect()
            ->route('admin.localization.index')
            ->with('success', "Страна {$country->name} успешно обновлена");
    }

    /**
     * Create or update country settings (API)
     */
    public function storeSettings(Request $request, string $countryCode): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_format' => 'required|string|max:50',
                'time_format' => 'required|string|max:50',
                'decimal_separator' => 'required|string|max:5',
                'thousands_separator' => 'required|string|max:5',
                'decimal_places' => 'required|integer|min:0|max:10',
                'currency_code' => 'required|string|max:10',
                'currency_symbol' => 'required|string|max:10',
                'locale' => 'required|string|max:20',
                'timezone' => 'required|string|max:50',
                'active' => 'boolean',
                'translations' => 'array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $country = Country::where('code', $countryCode)->first();
            if (!$country) {
                return response()->json([
                    'success' => false,
                    'message' => 'Страна не найдена',
                ], 404);
            }

            // Сохраняем настройки как key-value пары
            $settingsData = [
                'date_format' => $request->date_format,
                'time_format' => $request->time_format,
                'decimal_separator' => $request->decimal_separator,
                'thousands_separator' => $request->thousands_separator,
                'decimal_places' => $request->decimal_places,
                'currency_code' => $request->currency_code,
                'currency_symbol' => $request->currency_symbol,
                'locale' => $request->locale,
                'timezone' => $request->timezone,
            ];

            foreach ($settingsData as $key => $value) {
                LocalizationSetting::set(
                    $country->id,
                    $key,
                    $value,
                    'string',
                    'general',
                    ''
                );
            }

            if ($request->has('translations')) {
                LocalizationSetting::set(
                    $country->id,
                    'translations',
                    $request->translations,
                    'json',
                    'translation',
                    'Переводы для страны'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Настройки успешно сохранены',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving localization settings', [
                'country' => $countryCode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при сохранении настроек: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete country
     */
    public function destroy(string $code)
    {
        $country = Country::where('code', $code)->firstOrFail();
        $name = $country->name;

        // Удалить связанные настройки
        LocalizationSetting::where('country_id', $country->id)->delete();
        
        $country->delete();

        return redirect()
            ->route('admin.localization.index')
            ->with('success', "Страна {$name} успешно удалена");
    }

    /**
     * Save setting for a country
     */
    public function saveSetting(Request $request, string $code)
    {
        $country = Country::where('code', $code)->firstOrFail();

        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'nullable',
            'type' => 'required|string|in:string,number,boolean,json,array',
            'group' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        LocalizationSetting::set(
            $country->id,
            $validated['key'],
            $validated['value'],
            $validated['type'],
            $validated['group'],
            $validated['description'] ?? ''
        );

        return redirect()
            ->route('admin.localization.settings', $code)
            ->with('success', 'Настройка успешно сохранена');
    }

    /**
     * Delete setting for a country
     */
    public function deleteSetting(Request $request, string $code)
    {
        $country = Country::where('code', $code)->firstOrFail();

        $validated = $request->validate([
            'key' => 'required|string',
        ]);

        $setting = LocalizationSetting::where('country_id', $country->id)
            ->where('key', $validated['key'])
            ->first();

        if ($setting && !$setting->is_system) {
            $setting->delete();
            return redirect()
                ->route('admin.localization.settings', $code)
                ->with('success', 'Настройка успешно удалена');
        }

        return redirect()
            ->route('admin.localization.settings', $code)
            ->with('error', 'Нельзя удалить системную настройку');
    }

    /**
     * Import preset countries
     */
    public function importPresets(Request $request)
    {
        $validated = $request->validate([
            'countries' => 'required|array',
            'countries.*' => 'string|size:2',
        ]);

        $presets = config('localization.preset_countries', []);
        $imported = 0;

        foreach ($validated['countries'] as $code) {
            if (!isset($presets[$code])) {
                continue;
            }

            if (Country::where('code', $code)->exists()) {
                continue;
            }

            Country::create(array_merge($presets[$code], ['code' => $code]));
            $imported++;
        }

        return redirect()
            ->route('admin.localization.index')
            ->with('success', "Импортировано стран: {$imported}");
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        $this->localizationService->clearCache();

        return redirect()
            ->route('admin.localization.index')
            ->with('success', 'Кеш успешно очищен');
    }

    /**
     * Get statistics (API)
     */
    public function stats(): JsonResponse
    {
        return response()->json($this->localizationService->getStats());
    }

    /**
     * Get countries list (API)
     */
    public function countries(): JsonResponse
    {
        $countries = Country::withCount('settings')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    /**
     * Get country settings (API)
     */
    public function countrySettings(string $code): JsonResponse
    {
        $country = Country::where('code', $code)->first();

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
            ], 404);
        }

        $settings = LocalizationSetting::getAllForCountry($country->id);

        return response()->json([
            'success' => true,
            'data' => [
                'country' => $country,
                'settings' => $settings,
            ],
        ]);
    }

    /**
     * Get formatted date for a country
     */
    public function formatDate(Request $request, string $countryCode): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $formatted = $this->localizationService->formatDate($request->date, $countryCode);

            return response()->json([
                'success' => true,
                'formatted' => $formatted,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error formatting date', [
                'country' => $countryCode,
                'date' => $request->date,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка форматирования даты: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get formatted time for a country
     */
    public function formatTime(Request $request, string $countryCode): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'time' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $formatted = $this->localizationService->formatTime($request->time, $countryCode);

            return response()->json([
                'success' => true,
                'formatted' => $formatted,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error formatting time', [
                'country' => $countryCode,
                'time' => $request->time,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка форматирования времени: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get formatted currency for a country
     */
    public function formatCurrency(Request $request, string $countryCode): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $formatted = $this->localizationService->formatCurrency($request->amount, $countryCode);

            return response()->json([
                'success' => true,
                'formatted' => $formatted,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error formatting currency', [
                'country' => $countryCode,
                'amount' => $request->amount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка форматирования валюты: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get translation for a key
     */
    public function translate(Request $request, string $countryCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'locale' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $translated = $this->localizationService->translate(
            $request->key,
            null,
            $countryCode
        );

        return response()->json([
            'success' => true,
            'translated' => $translated,
        ]);
    }

    /**
     * Get all translations for a country
     */
    public function getTranslations(Request $request, string $countryCode): JsonResponse
    {
        $locale = $request->query('locale');
        $country = Country::where('code', $countryCode)->first();
        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
            ], 404);
        }

        $setting = LocalizationSetting::where('country_id', $country->id)->first();
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found for this country',
            ], 404);
        }

        $translations = $setting->translations ?? [];
        if ($locale && isset($translations[$locale])) {
            $translations = $translations[$locale];
        }

        return response()->json([
            'success' => true,
            'data' => $translations,
        ]);
    }

    /**
     * Import translations from JSON
     */
    public function importTranslations(Request $request, string $countryCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
            'locale' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $country = Country::where('code', $countryCode)->first();
        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
            ], 404);
        }

        $setting = LocalizationSetting::where('country_id', $country->id)->first();
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found for this country',
            ], 404);
        }

        $currentTranslations = $setting->translations ?? [];
        $locale = $request->locale ?? $setting->locale ?? 'ru_RU';

        // Merge translations
        $currentTranslations[$locale] = array_merge(
            $currentTranslations[$locale] ?? [],
            $request->translations
        );

        $setting->update(['translations' => $currentTranslations]);

        return response()->json([
            'success' => true,
            'message' => 'Translations imported successfully',
            'data' => $setting->fresh(),
        ]);
    }

    /**
     * Export translations as JSON
     */
    public function exportTranslations(string $countryCode): JsonResponse
    {
        $country = Country::where('code', $countryCode)->first();
        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
            ], 404);
        }

        $setting = LocalizationSetting::where('country_id', $country->id)->first();
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found for this country',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'country' => $country->only(['code', 'name']),
                'settings' => $setting->only([
                    'date_format', 'time_format', 'decimal_separator',
                    'thousands_separator', 'decimal_places', 'currency_code',
                    'currency_symbol', 'locale', 'timezone', 'active'
                ]),
                'translations' => $setting->translations ?? [],
            ],
        ]);
    }

    /**
     * Get timezone offset for a country
     */
    public function timezoneOffset(string $countryCode): JsonResponse
    {
        $country = Country::where('code', $countryCode)->first();
        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
            ], 404);
        }

        $setting = LocalizationSetting::where('country_id', $country->id)->first();
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found for this country',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'offset' => $setting->getTimezoneOffset(),
            'timezone' => $setting->timezone,
        ]);
    }

    /**
     * Convert timezone for a country
     */
    public function convertTimezone(Request $request, string $countryCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'datetime' => 'required|date',
            'to_timezone' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $country = Country::where('code', $countryCode)->first();
        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found',
            ], 404);
        }

        $setting = LocalizationSetting::where('country_id', $country->id)->first();
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Settings not found for this country',
            ], 404);
        }

        $converted = $setting->convertTimezone(
            $request->datetime,
            $request->to_timezone
        );

        return response()->json([
            'success' => true,
            'datetime' => $converted->format('Y-m-d H:i:s'),
            'timezone' => $converted->getTimezone()->getName(),
        ]);
    }
}
