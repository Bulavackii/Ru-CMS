<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\GenerateSitemap;
use App\Jobs\ClearOldCache;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_sitemap_job_can_be_dispatched()
    {
        Bus::fake();

        GenerateSitemap::dispatch();

        Bus::assertDispatched(GenerateSitemap::class);
    }

    public function test_clear_cache_job_can_be_dispatched()
    {
        Bus::fake();

        ClearOldCache::dispatch();

        Bus::assertDispatched(ClearOldCache::class);
    }

    public function test_sitemap_generation_creates_file()
    {
        // Создаем тестовые данные
        $this->seed();

        $job = new GenerateSitemap();

        // Запускаем job синхронно для теста
        $job->handle();

        // Проверяем, что файл создан
        $sitemapPath = public_path('sitemap.xml');
        $this->assertFileExists($sitemapPath);

        // Проверяем содержимое
        $content = File::get($sitemapPath);
        $this->assertStringContainsString('<?xml version="1.0"', $content);
        $this->assertStringContainsString('<urlset', $content);
    }

    public function test_cache_clear_job_works()
    {
        // ClearOldCache чистит кэш по тегам (home/news/theme/modules), а не
        // произвольные ключи — обычный Cache::put() без тега им не затронется.
        Cache::tags(['home'])->put('test_key', 'test_value', 60);
        Cache::tags(['home'])->put('home_data', ['test'], 60);

        $job = new ClearOldCache();
        $job->handle();

        // Проверяем, что кэш очищен
        $this->assertNull(Cache::tags(['home'])->get('test_key'));
        $this->assertNull(Cache::tags(['home'])->get('home_data'));
    }

    public function test_jobs_have_correct_timeout()
    {
        $sitemapJob = new GenerateSitemap();
        $this->assertEquals(300, $sitemapJob->timeout);

        $cacheJob = new ClearOldCache();
        $this->assertEquals(60, $cacheJob->timeout);
    }
}
