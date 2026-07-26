<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notifications\Console\Commands\SeedDefaultNotificationCommand;
use Modules\Notifications\Models\Notification;
use Tests\TestCase;

/**
 * 🔔 Модуль «Уведомления» — баннеры для посетителей сайта.
 *
 * По модулю не было ни одного теста, а в нём жили молчаливые баги: уведомление
 * без указанной страницы не показывалось никому, маски маршрутов не работали,
 * половина полей формы не сохранялась, предпросмотр отдавал 500, а сброс кеша
 * чистил несуществующий ключ.
 */
class NotificationsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Технические работы',
            'message' => '<p>Сайт может быть недоступен</p>',
            'type' => 'html',
            'target' => 'all',
            'position' => 'top',
            'duration' => 0,
            'icon' => '🔧',
            'route_filter' => '',
            'priority' => 40,
            'enabled' => '1',
            '_submitted' => '1',
        ], $overrides);
    }

    // ── Админка ───────────────────────────────────────────────────────────

    public function test_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Уведомлений пока нет');
    }

    public function test_store_saves_every_field_from_the_form(): void
    {
        // Раньше store() валидировал укороченный набор: тип html отклонялся,
        // а priority, starts_at/ends_at и created_by молча терялись.
        $response = $this->actingAs($this->admin())->post(route('admin.notifications.store'), $this->payload([
            'starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ]));

        $response->assertRedirect(route('admin.notifications.index'));

        $notification = Notification::firstOrFail();
        $this->assertSame('html', $notification->type);
        $this->assertSame(40, $notification->priority);
        $this->assertTrue($notification->enabled);
        $this->assertNotNull($notification->starts_at);
        $this->assertNotNull($notification->ends_at);
        $this->assertNotNull($notification->created_by);
        // Пустая строка фильтра должна стать NULL — это признак «на всех страницах»
        $this->assertNull($notification->route_filter);
    }

    public function test_unchecked_enabled_is_saved_as_disabled(): void
    {
        // Галочку «Включено» нельзя было снять: store() жёстко ставил true
        $payload = $this->payload();
        unset($payload['enabled']);

        $this->actingAs($this->admin())->post(route('admin.notifications.store'), $payload);

        $this->assertFalse(Notification::firstOrFail()->enabled);
    }

    public function test_toggle_and_destroy(): void
    {
        $admin = $this->admin();
        $notification = Notification::create($this->payload(['enabled' => true, 'route_filter' => null]));

        $this->actingAs($admin)->patch(route('admin.notifications.toggle', $notification))
            ->assertRedirect();
        $this->assertFalse($notification->fresh()->enabled);

        $this->actingAs($admin)->delete(route('admin.notifications.destroy', $notification))
            ->assertRedirect(route('admin.notifications.index'));
        $this->assertNull(Notification::find($notification->id));
    }

    public function test_bulk_actions(): void
    {
        $admin = $this->admin();
        $first = Notification::create($this->payload(['title' => 'Первое', 'enabled' => false, 'route_filter' => null]));
        $second = Notification::create($this->payload(['title' => 'Второе', 'enabled' => false, 'route_filter' => null]));
        $ids = [$first->id, $second->id];

        $this->actingAs($admin)->post(route('admin.notifications.bulk'), ['action' => 'enable', 'selected' => $ids]);
        $this->assertTrue($first->fresh()->enabled);
        $this->assertTrue($second->fresh()->enabled);

        $this->actingAs($admin)->post(route('admin.notifications.bulk'), ['action' => 'disable', 'selected' => $ids]);
        $this->assertFalse($first->fresh()->enabled);

        $this->actingAs($admin)->post(route('admin.notifications.bulk'), ['action' => 'delete', 'selected' => $ids]);
        $this->assertSame(0, Notification::count());
    }

    public function test_bulk_without_selection_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.notifications.bulk'), ['action' => 'delete'])
            ->assertSessionHasErrors('selected');
    }

    public function test_preview_page_opens(): void
    {
        // Маршрут был, а вьюхи Notifications::admin.preview не существовало — 500
        $notification = Notification::create($this->payload(['route_filter' => null]));

        $response = $this->actingAs($this->admin())
            ->get(route('admin.notifications.preview', $notification));

        $response->assertStatus(200);
        $response->assertSee('Показывается ли сейчас');
    }

    public function test_search_filter_finds_notification(): void
    {
        Notification::create($this->payload(['title' => 'Отключение электричества', 'route_filter' => null]));
        Notification::create($this->payload(['title' => 'Скидки', 'route_filter' => null]));

        $response = $this->actingAs($this->admin())
            ->get(route('admin.notifications.index', ['search' => 'электричества']));

        $response->assertStatus(200);
        $response->assertSee('Отключение электричества');
        $response->assertDontSee('Скидки');
    }

    // ── Показ на сайте ────────────────────────────────────────────────────

    public function test_notification_without_route_filter_is_shown_everywhere(): void
    {
        // Главный баг: matchesRouteFilter('') возвращал false, поэтому уведомление
        // «на всех страницах» (route_filter = NULL) не видел вообще никто.
        Notification::create($this->payload([
            'title' => 'Видно везде',
            'message' => 'Текст объявления',
            'enabled' => true,
            'route_filter' => null,
        ]));

        $this->get('/')->assertStatus(200)->assertSee('Видно везде');
    }

    public function test_route_mask_limits_pages(): void
    {
        Notification::create($this->payload([
            'title' => 'Только новости',
            'enabled' => true,
            'route_filter' => '/news/*',
        ]));

        $this->get('/')->assertDontSee('Только новости');
    }

    public function test_disabled_and_expired_are_not_shown(): void
    {
        Notification::create($this->payload(['title' => 'Выключенное', 'enabled' => false, 'route_filter' => null]));
        Notification::create($this->payload([
            'title' => 'Просроченное',
            'enabled' => true,
            'route_filter' => null,
            'ends_at' => now()->subDay(),
        ]));

        $response = $this->get('/');
        $response->assertDontSee('Выключенное');
        $response->assertDontSee('Просроченное');
    }

    public function test_admin_only_notification_is_hidden_from_guest(): void
    {
        Notification::create($this->payload([
            'title' => 'Только для админов',
            'enabled' => true,
            'route_filter' => null,
            'target' => 'admin',
        ]));

        $this->get('/')->assertDontSee('Только для админов');
        $this->actingAs($this->admin())->get('/')->assertSee('Только для админов');
    }

    public function test_cache_is_dropped_on_change(): void
    {
        $notification = Notification::create($this->payload([
            'title' => 'Живое объявление',
            'enabled' => true,
            'route_filter' => null,
        ]));

        $this->get('/')->assertSee('Живое объявление');

        $notification->update(['enabled' => false]);

        // Раньше выдача жила в кеше 5 минут: контроллер чистил ключ
        // notifications_active, которого не существует
        $this->get('/')->assertDontSee('Живое объявление');
    }

    // ── Демо-уведомление после установки ──────────────────────────────────

    public function test_default_notification_is_seeded_disabled_and_idempotent(): void
    {
        SeedDefaultNotificationCommand::seed(false);
        SeedDefaultNotificationCommand::seed(false);

        $this->assertSame(1, Notification::count());

        $notification = Notification::firstOrFail();
        $this->assertSame(SeedDefaultNotificationCommand::DEMO_TITLE, $notification->title);
        // Образец для правки, а не живое объявление на свежем сайте
        $this->assertFalse($notification->enabled);

        $this->get('/')->assertDontSee($notification->title);
    }

    // ── Разведение с центром уведомлений админки ──────────────────────────

    public function test_admin_notification_center_has_its_own_routes(): void
    {
        // URI обеих систем совпадали: колокольчик в шапке получал HTML вместо
        // JSON, а его кнопка удаления била по баннерам сайта.
        $this->assertSame('/admin/notification-center', route('admin.notification_center.index', [], false));

        $this->actingAs($this->admin())
            ->getJson(route('admin.notification_center.index'))
            ->assertStatus(200)
            ->assertJsonStructure(['notifications', 'unread_count']);

        // А /admin/notifications остаётся за модулем и отдаёт страницу списка
        $this->actingAs($this->admin())
            ->get(route('admin.notifications.index'))
            ->assertStatus(200)
            ->assertViewIs('Notifications::admin.index');
    }
}
