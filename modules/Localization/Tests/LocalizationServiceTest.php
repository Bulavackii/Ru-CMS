<?php

namespace Modules\Localization\Tests;

use Tests\TestCase;
use Modules\Localization\Models\Country;
use Modules\Localization\Models\LocalizationSetting;
use Modules\Localization\Services\LocalizationService;

class LocalizationServiceTest extends TestCase
{
    private LocalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LocalizationService();
    }

    /** @test */
    public function it_can_create_a_country()
    {
        $country = $this->service->createOrUpdateCountry([
            'code' => 'TEST',
            'name' => 'Test Country',
            'currency_code' => 'TST',
            'locale' => 'en_US',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        $this->assertNotNull($country);
        $this->assertEquals('TEST', $country->code);
        $this->assertEquals('Test Country', $country->name);
    }

    /** @test */
    public function it_can_format_currency()
    {
        $country = Country::where('code', 'RU')->first();

        if (!$country) {
            $country = $this->service->createOrUpdateCountry([
                'code' => 'RU',
                'name' => 'Russia',
                'currency_code' => 'RUB',
                'currency_symbol' => '₽',
                'locale' => 'ru_RU',
                'timezone' => 'Europe/Moscow',
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
            ]);
        }

        $formatted = $this->service->formatCurrency(1234.56, 'RU');
        $this->assertStringContainsString('1234', $formatted);
        $this->assertStringContainsString('₽', $formatted);
    }

    /** @test */
    public function it_can_format_date()
    {
        $country = Country::where('code', 'RU')->first();

        if (!$country) {
            $country = $this->service->createOrUpdateCountry([
                'code' => 'RU',
                'name' => 'Russia',
                'currency_code' => 'RUB',
                'locale' => 'ru_RU',
                'timezone' => 'Europe/Moscow',
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
            ]);
        }

        $testDate = '2025-12-27 15:30:00';
        $formatted = $this->service->formatDate($testDate, 'RU');

        // Формат должен быть d.m.Y
        $this->assertEquals('27.12.2025', $formatted);
    }

    /** @test */
    public function it_can_save_and_retrieve_settings()
    {
        $country = $this->service->createOrUpdateCountry([
            'code' => 'TEST',
            'name' => 'Test Country',
            'currency_code' => 'TST',
            'locale' => 'en_US',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
        ]);

        // Сохраняем настройку
        $result = $this->service->saveSetting('TEST', 'test_key', 'test_value', 'string', 'general', 'Test description');
        $this->assertTrue($result);

        // Получаем настройку
        $value = $this->service->getSetting('test_key', null, 'TEST');
        $this->assertEquals('test_value', $value);

        // Удаляем настройку
        $result = $this->service->deleteSetting('TEST', 'test_key');
        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_all_countries()
    {
        $countries = $this->service->getCountries();
        $this->assertGreaterThan(0, $countries->count());
    }

    /** @test */
    public function it_can_translate_text()
    {
        $country = $this->service->createOrUpdateCountry([
            'code' => 'TEST',
            'name' => 'Test Country',
            'currency_code' => 'TST',
            'locale' => 'en_US',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'translations' => [
                'welcome' => 'Welcome to Test Country!',
            ],
        ]);

        $translated = $this->service->translate('welcome', 'Default', 'TEST');
        $this->assertEquals('Welcome to Test Country!', $translated);
    }

    /** @test */
    public function it_can_format_numbers()
    {
        $country = $this->service->createOrUpdateCountry([
            'code' => 'TEST',
            'name' => 'Test Country',
            'currency_code' => 'TST',
            'locale' => 'en_US',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'decimal_places' => 2,
        ]);

        $formatted = $this->service->formatNumber(1234567.89, 'TEST');
        $this->assertEquals('1,234,567.89', $formatted);
    }

    /** @test */
    public function it_can_get_stats()
    {
        $stats = $this->service->getStats();

        $this->assertArrayHasKey('total_countries', $stats);
        $this->assertArrayHasKey('active_countries', $stats);
        $this->assertArrayHasKey('total_settings', $stats);
        $this->assertArrayHasKey('system_settings', $stats);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        // Заполняем кеш
        $this->service->getCountries();

        // Очищаем кеш
        $this->service->clearCache();

        // Проверяем, что кеш очищен (новый запрос должен работать)
        $countries = $this->service->getCountries();
        $this->assertNotNull($countries);
    }
}
