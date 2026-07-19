<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\News\Models\News;
use Modules\Categories\Models\Category;
use Modules\Slideshow\Models\Slideshow;
use Modules\Menu\Models\Menu;
use Modules\Menu\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads_successfully()
    {
        // Публикация новости нужна, иначе HomeController показывает
        // приветственную страницу для пустых установок (frontend.welcome).
        News::factory()->create(['published' => true]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('frontend.home');
    }

    public function test_home_page_with_cached_data()
    {
        // Создаем тестовые данные
        $category = Category::factory()->create();
        $news = News::factory()->create([
            'template' => 'default',
            'published' => true,
        ]);
        $news->categories()->attach($category->id);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('templates');
        $this->assertArrayHasKey('default', $response->viewData('templates'));
    }

    public function test_home_page_with_filter()
    {
        $category = Category::factory()->create();
        $news1 = News::factory()->create([
            'template' => 'products',
            'published' => true,
        ]);
        $news1->categories()->attach($category->id);

        $news2 = News::factory()->create([
            'template' => 'products',
            'published' => true,
        ]);

        $response = $this->get("/?category_products={$category->id}");

        $response->assertStatus(200);
        $templates = $response->viewData('templates');
        $this->assertCount(1, $templates['products']);
    }

    public function test_home_page_caching()
    {
        News::factory()->create(['published' => true]);
        Category::factory()->create();

        // Первый запрос
        $response1 = $this->get('/');
        $this->assertGreaterThan(0, $response1->viewData('categories')->count());

        // Второй запрос должен использовать кэш
        $response2 = $this->get('/');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }
}
