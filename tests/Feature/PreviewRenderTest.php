<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Menu\Models\Menu;
use Tests\TestCase;

/** ВРЕМЕННЫЙ тест для визуальной/интерактивной проверки — рендерит вьюху в public/. */
class PreviewRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_previews(): void
    {
        $port = 8899;
        config(['app.url' => "http://127.0.0.1:$port"]);
        URL::forceRootUrl("http://127.0.0.1:$port");

        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Админ']);
        $m1 = Menu::create(['title' => 'Главное меню сайта', 'position' => 'header', 'active' => true]);
        foreach (['Главная', 'О нас', 'Услуги', 'Контакты'] as $i => $t) {
            $m1->items()->create(['title' => $t, 'type' => 'url', 'url' => '/', 'active' => true, 'order' => $i]);
        }
        $root  = $m1->items()->create(['title' => 'Каталог', 'type' => 'url', 'url' => '/cat', 'active' => true, 'order' => 10]);
        $child = $m1->items()->create(['title' => 'Ноутбуки', 'type' => 'url', 'url' => '/cat/nb', 'active' => true, 'parent_id' => $root->id, 'order' => 0]);
        $m1->items()->create(['title' => 'Игровые', 'type' => 'url', 'url' => '/cat/nb/g', 'active' => false, 'parent_id' => $child->id, 'order' => 0]);

        $resp = $this->actingAs($admin)->get(route('admin.menus.edit', $m1));
        $resp->assertStatus(200);
        file_put_contents(public_path('_preview_menuedit.html'), $resp->getContent());
        $this->assertTrue(true);
    }
}
