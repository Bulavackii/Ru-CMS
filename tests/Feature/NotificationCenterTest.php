<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Выпадающий центр уведомлений в шапке панели.
 */
class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function makeNotification(array $attributes = []): int
    {
        return DB::table('admin_notifications')->insertGetId(array_merge([
            'user_id' => null,
            'type' => 'info',
            'title' => 'Заголовок уведомления',
            'message' => 'Текст уведомления.',
            'read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    public function test_endpoint_returns_json(): void
    {
        $admin = $this->admin();
        $this->makeNotification(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->getJson('/admin/notification-center')
            ->assertOk()
            ->assertJsonStructure(['notifications', 'unread_count'])
            ->assertJsonPath('unread_count', 1);
    }

    public function test_single_notification_can_be_marked_read(): void
    {
        // Маршрут существовал, но компонент его не вызывал — отдельное
        // уведомление нельзя было прочитать вообще, только все разом.
        $admin = $this->admin();
        $id = $this->makeNotification(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->postJson('/admin/notification-center/' . $id . '/read')
            ->assertOk();

        $this->assertTrue((bool) DB::table('admin_notifications')->where('id', $id)->value('read'));
    }

    public function test_mark_all_as_read(): void
    {
        $admin = $this->admin();
        $this->makeNotification(['user_id' => $admin->id]);
        $this->makeNotification(['user_id' => $admin->id]);

        $this->actingAs($admin)->postJson('/admin/notification-center/mark-all-read')->assertOk();

        $this->assertSame(0, DB::table('admin_notifications')->where('read', false)->count());
    }

    public function test_notification_can_be_deleted(): void
    {
        $admin = $this->admin();
        $id = $this->makeNotification(['user_id' => $admin->id]);

        $this->actingAs($admin)->deleteJson('/admin/notification-center/' . $id)->assertOk();

        $this->assertDatabaseMissing('admin_notifications', ['id' => $id]);
    }

    public function test_foreign_notification_is_not_touched(): void
    {
        $admin = $this->admin();
        $stranger = $this->admin();
        $id = $this->makeNotification(['user_id' => $stranger->id]);

        $this->actingAs($admin)->deleteJson('/admin/notification-center/' . $id)->assertOk();

        // Ответ всегда «успех», но чужая запись остаётся на месте.
        $this->assertDatabaseHas('admin_notifications', ['id' => $id]);
    }

    public function test_foreign_notification_is_not_listed(): void
    {
        $admin = $this->admin();
        $stranger = $this->admin();
        $this->makeNotification(['user_id' => $stranger->id]);

        $this->actingAs($admin)
            ->getJson('/admin/notification-center')
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'notifications');
    }

    public function test_endpoint_is_closed_for_guests(): void
    {
        $this->get('/admin/notification-center')->assertRedirect();
    }

    public function test_admin_layout_language_follows_locale(): void
    {
        // Атрибут lang был прибит к ru: и скринридер, и Intl в центре
        // уведомлений получали неверный язык на любой другой локали.
        $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'en'])
            ->get('/admin')
            ->assertOk()
            ->assertSee('<html lang="en"', false);
    }
}
