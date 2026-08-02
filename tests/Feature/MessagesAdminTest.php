<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Messages\Models\Message;
use Tests\TestCase;

/**
 * Раздел «Сообщения» в панели: вкладки, поиск и массовые действия.
 */
class MessagesAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $peer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->peer = User::factory()->create(['is_admin' => true]);
    }

    private function makeMessage(array $attributes = []): Message
    {
        return Message::create(array_merge([
            'user_id' => $this->peer->id,
            'to_user_id' => $this->admin->id,
            'subject' => 'Тема письма',
            'body' => 'Текст письма для проверки.',
        ], $attributes));
    }

    public function test_index_opens(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['app_locale' => 'ru'])
            ->get('/admin/messages')
            ->assertOk()
            ->assertViewIs('messages::admin.index')
            ->assertViewHas('counts')
            ->assertSee('Сообщения');
    }

    public function test_archived_tab_shows_archived_messages(): void
    {
        // Базовый запрос применял notArchived() безусловно, а фильтр поверх
        // добавлял archived() — вкладка не могла показать ничего никогда.
        $archived = $this->makeMessage(['subject' => 'Убрано в архив', 'archived_at' => now()]);
        $this->makeMessage(['subject' => 'Обычное письмо']);

        $response = $this->actingAs($this->admin)->get('/admin/messages?filter=archived');

        $response->assertOk();
        $ids = $response->viewData('messages')->pluck('id')->all();

        $this->assertSame([$archived->id], $ids);
    }

    public function test_archived_messages_are_hidden_from_other_tabs(): void
    {
        $this->makeMessage(['subject' => 'Убрано в архив', 'archived_at' => now()]);
        $visible = $this->makeMessage(['subject' => 'Обычное письмо']);

        $response = $this->actingAs($this->admin)->get('/admin/messages');

        $this->assertSame([$visible->id], $response->viewData('messages')->pluck('id')->all());
    }

    public function test_archive_counter_is_present(): void
    {
        // Счётчика архива не было в массиве вовсе — вкладка стояла без числа.
        $this->makeMessage(['archived_at' => now()]);
        $this->makeMessage(['archived_at' => now()]);

        $counts = $this->actingAs($this->admin)->get('/admin/messages')->viewData('counts');

        $this->assertArrayHasKey('archived', $counts);
        $this->assertSame(2, $counts['archived']);
    }

    public function test_search_filters_by_subject_and_body(): void
    {
        $needle = $this->makeMessage(['subject' => 'Сломалась корзина']);
        $this->makeMessage(['subject' => 'Совсем про другое', 'body' => 'И тело другое.']);

        $response = $this->actingAs($this->admin)->get('/admin/messages?search=корзина');

        $this->assertSame([$needle->id], $response->viewData('messages')->pluck('id')->all());
    }

    public function test_search_survives_pagination(): void
    {
        // Раньше appends() перечислял параметры вручную — любой новый
        // параметр терялся при переходе по страницам.
        $response = $this->actingAs($this->admin)->get('/admin/messages?search=корзина&filter=inbox');

        $this->assertStringContainsString('search=', $response->viewData('messages')->url(1));
        $this->assertStringContainsString('filter=', $response->viewData('messages')->url(1));
    }

    public function test_unread_tab_shows_only_incoming_unread(): void
    {
        $unread = $this->makeMessage(['is_read' => false]);
        $this->makeMessage(['is_read' => true]);
        // Исходящее письмо во «Входящих непрочитанных» быть не должно.
        $this->makeMessage(['user_id' => $this->admin->id, 'to_user_id' => $this->peer->id, 'is_read' => false]);

        $response = $this->actingAs($this->admin)->get('/admin/messages?filter=unread');

        $this->assertSame([$unread->id], $response->viewData('messages')->pluck('id')->all());
    }

    public function test_foreign_messages_are_not_listed(): void
    {
        $stranger = User::factory()->create(['is_admin' => true]);
        Message::create([
            'user_id' => $stranger->id,
            'to_user_id' => $this->peer->id,
            'subject' => 'Чужая переписка',
            'body' => 'Не для этого администратора.',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/messages');

        $this->assertCount(0, $response->viewData('messages'));
    }

    public function test_bulk_archive_moves_message_to_archive_tab(): void
    {
        $message = $this->makeMessage();

        $this->actingAs($this->admin)
            ->post('/admin/messages/bulk-action', ['action' => 'archive', 'ids' => [$message->id]]);

        $this->assertNotNull($message->fresh()->archived_at);

        $ids = $this->actingAs($this->admin)
            ->get('/admin/messages?filter=archived')
            ->viewData('messages')->pluck('id')->all();

        $this->assertSame([$message->id], $ids);
    }

    public function test_index_is_closed_for_guests(): void
    {
        $this->get('/admin/messages')->assertRedirect();
    }
}
