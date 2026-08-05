<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class UpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Адрес сервера обновлений ПУСТ по умолчанию: проверка выключена,
        // чтобы CMS не ходила наружу без ведома владельца. Тестам, которые
        // проверяют саму проверку, адрес нужен — задаём его явно.
        config(['app.update_server_url' => 'https://updates.example.test/api']);

        $this->service = app(UpdateService::class);
    }

    /** @test */
    public function it_can_check_for_updates()
    {
        Http::fake([
            '*' => Http::response([
                'latest_version' => '2.0.1',
                'available' => true,
                'changelog' => 'Bug fixes',
            ], 200),
        ]);

        $result = $this->service->checkForUpdates();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
    }

    /** @test */
    public function it_handles_update_check_failure()
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $result = $this->service->checkForUpdates();

        $this->assertIsArray($result);
        $this->assertFalse($result['available'] ?? false);
    }

    /** @test */
    public function it_caches_update_check_results()
    {
        Http::fake([
            '*' => Http::response([
                'latest_version' => '2.0.1',
                'available' => true,
            ], 200),
        ]);

        // Первый вызов
        $result1 = $this->service->checkForUpdates();
        
        // Второй вызов должен использовать кеш
        $result2 = $this->service->checkForUpdates();

        $this->assertEquals($result1, $result2);
        Http::assertSentCount(1); // Должен быть только один HTTP запрос
    }

    /** @test */
    public function it_does_not_call_anything_without_an_update_server()
    {
        // Ключевая гарантия: из коробки CMS не стучится никуда. Раньше здесь
        // стоял чужой адрес по умолчанию, и главная страница панели отправляла
        // на него лицензионный ключ и версии окружения при каждой отрисовке.
        config(['app.update_server_url' => '']);
        Cache::forget('updates:available');
        Http::fake();

        $result = app(UpdateService::class)->checkForUpdates();

        Http::assertNothingSent();
        $this->assertFalse($result['available']);
    }

    /** @test */
    public function it_stays_silent_in_standalone_mode()
    {
        // Автономный режим гасит проверку, даже если адрес сервера задан.
        config(['app.standalone' => true]);
        Cache::forget('updates:available');
        Http::fake();

        $result = app(UpdateService::class)->checkForUpdates();

        Http::assertNothingSent();
        $this->assertFalse($result['available']);
    }
}

