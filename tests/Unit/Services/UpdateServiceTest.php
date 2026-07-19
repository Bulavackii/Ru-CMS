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
        $this->service = app(UpdateService::class);
    }

    /** @test */
    public function it_can_check_for_updates()
    {
        Http::fake([
            '*' => Http::response([
                'latest_version' => '2.0.1',
                'update_available' => true,
                'changelog' => 'Bug fixes',
            ], 200),
        ]);

        $result = $this->service->checkForUpdates();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('update_available', $result);
    }

    /** @test */
    public function it_handles_update_check_failure()
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $result = $this->service->checkForUpdates();

        $this->assertIsArray($result);
        $this->assertFalse($result['update_available'] ?? false);
    }

    /** @test */
    public function it_caches_update_check_results()
    {
        Http::fake([
            '*' => Http::response([
                'latest_version' => '2.0.1',
                'update_available' => true,
            ], 200),
        ]);

        // Первый вызов
        $result1 = $this->service->checkForUpdates();
        
        // Второй вызов должен использовать кеш
        $result2 = $this->service->checkForUpdates();

        $this->assertEquals($result1, $result2);
        Http::assertSentCount(1); // Должен быть только один HTTP запрос
    }
}

