<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\HomeController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Ссылки в панель управления на публичной части сайта.
 *
 * Кнопка «Админка» в шапке проверяла `$user->is_admin` — а `$user` в
 * шапку никто не передаёт: партиал подключается через @include, значит
 * переменная приходит из вьюхи-родителя, какой бы она там ни была.
 * Признак администратора зависел от страницы, а не от того, кто вошёл.
 * Теперь спрашиваем ВОШЕДШЕГО пользователя и тот же признак, что
 * проверяет AdminMiddleware.
 */
class FrontendAdminLinkTest extends TestCase
{
    use RefreshDatabase;

    /** Разметка кнопки, а не слово «Админка»: оно встречается и в текстах. */
    private const BUTTON = 'hdr-pill hdr-pill--accent';

    public function test_guest_does_not_see_the_panel_button(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('admin.dashboard'), $html);
    }

    public function test_regular_user_does_not_see_the_panel_button(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('admin.dashboard'), $html);
    }

    public function test_admin_sees_the_panel_button(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $html = $this->actingAs($admin)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.dashboard'), $html);
        $this->assertStringContainsString(self::BUTTON, $html);
    }

    public function test_page_variable_named_user_cannot_reveal_the_button(): void
    {
        // Ровно тот случай, из-за которого правка и понадобилась: страница
        // передаёт во вьюху свой $user — профиль, автор материала, список
        // пользователей. У @include видимость данных общая, поэтому шапка
        // видела именно эту переменную, а не того, кто вошёл.
        $admin = User::factory()->create(['is_admin' => true]);
        $guest = User::factory()->create(['is_admin' => false]);

        Route::get('/__probe-foreign-user', function () use ($admin) {
            return app(HomeController::class)->index(request())->with('user', $admin);
        })->middleware('web');

        $html = $this->actingAs($guest)->get('/__probe-foreign-user')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('admin.dashboard'), $html);
    }
}
