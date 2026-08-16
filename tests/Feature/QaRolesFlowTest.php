<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Menu\Models\Page;
use Modules\News\Models\News;
use Tests\TestCase;

/**
 * Сквозная проверка ролей: гость, обычный пользователь, администратор.
 *
 * Это не проверка отдельной функции, а обход системы «как пользователь»:
 * открыть, отправить, купить, промодерировать. Такие проверки ловят то, что
 * не видно поштучно — например, что страница ввода кода лежала под
 * middleware `guest` и второй шаг входа не спрашивался вовсе.
 *
 * ⚠️ Всё, что меняет данные, живёт ЗДЕСЬ, на отдельной базе. По боевой базе
 * владельца ходим только чтением.
 */
class QaRolesFlowTest extends TestCase
{
    use RefreshDatabase;

    private function админ(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function пользователь(): User
    {
        return User::factory()->create(['is_admin' => false]);
    }

    private function материал(array $поля = []): News
    {
        return News::create(array_merge([
            'title'     => 'Материал для проверки',
            'slug'      => 'material-' . uniqid(),
            'content'   => '<p>Текст материала</p>',
            'template'  => 'default',
            'published' => true,
        ], $поля));
    }

    // ─────────────────────────────── ГОСТЬ ──────────────────────────────

    /** Публичные страницы открываются без входа. */
    public function test_guest_sees_public_pages(): void
    {
        $материал = $this->материал();
        $страница = Page::create([
            'title' => 'О проекте', 'slug' => 'o-proverke',
            'content' => '<p>Текст</p>', 'published' => true,
        ]);

        $this->get('/')->assertOk();
        $this->get('/news')->assertOk();
        $this->get('/news/' . $материал->slug)->assertOk();
        $this->get('/page/' . $страница->slug)->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    /** Панель гостю закрыта — и это редирект на вход, а не 200 и не 500. */
    public function test_guest_is_kept_out_of_admin(): void
    {
        foreach (['/admin', '/admin/news', '/admin/users', '/admin/orders'] as $адрес) {
            $ответ = $this->get($адрес);

            $this->assertContains(
                $ответ->getStatusCode(),
                [302, 403],
                "Гость получил {$ответ->getStatusCode()} на {$адрес} — панель должна быть закрыта"
            );
        }
    }

    /** Неверный пароль не пускает и не роняет страницу. */
    public function test_wrong_password_is_rejected(): void
    {
        $человек = $this->пользователь();

        $this->post('/login', [
            'email' => $человек->email,
            'password' => 'заведомо-неверный',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    /** Верный пароль пускает. */
    public function test_correct_password_lets_in(): void
    {
        $человек = User::factory()->create(['is_admin' => false, 'password' => bcrypt('Parol12345!')]);

        $this->post('/login', [
            'email' => $человек->email,
            'password' => 'Parol12345!',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($человек->fresh());
    }

    /** Регистрация заводит учётную запись. */
    public function test_registration_creates_account(): void
    {
        // Пароль обязан отвечать политике: заглавные, строчные и цифра.
        // Плюс согласие с условиями — без него регистрация не проходит.
        $ответ = $this->post('/register', [
            'name' => 'Новый посетитель',
            'email' => 'novyy@example.com',
            'password' => 'Parol12345!',
            'password_confirmation' => 'Parol12345!',
            'terms_agree' => 1,
        ]);

        $ответ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'novyy@example.com']);

        // ⚠️ Новичок НЕ должен получить доступ в панель.
        $this->assertFalse((bool) User::where('email', 'novyy@example.com')->value('is_admin'));
    }

    /** Восстановление пароля не падает, даже если почта не настроена. */
    public function test_password_recovery_does_not_crash(): void
    {
        $человек = $this->пользователь();

        $ответ = $this->post('/forgot-password', ['email' => $человек->email]);
        $this->assertContains($ответ->getStatusCode(), [200, 302]);
    }

    // ────────────────────────── ОБЫЧНЫЙ ПОЛЬЗОВАТЕЛЬ ────────────────────

    /** Вошедший пользователь видит кабинет. */
    public function test_user_sees_cabinet(): void
    {
        $this->actingAs($this->пользователь());

        $this->get('/dashboard')->assertOk();
    }

    /**
     * Обычному пользователю панель закрыта.
     *
     * Это ключевая проверка разграничения: доступ даёт признак is_admin, а не
     * факт входа.
     */
    public function test_user_cannot_reach_admin(): void
    {
        $this->actingAs($this->пользователь());

        foreach (['/admin', '/admin/news', '/admin/users', '/admin/modules'] as $адрес) {
            $ответ = $this->get($адрес);

            $this->assertNotSame(
                200,
                $ответ->getStatusCode(),
                "Обычный пользователь открыл {$адрес} — это дыра в доступе"
            );
        }
    }

    // ─────────────────────────────── АДМИН ──────────────────────────────

    /** Ключевые разделы панели открываются. */
    public function test_admin_opens_core_sections(): void
    {
        $this->actingAs($this->админ());

        foreach ([
            '/admin', '/admin/news', '/admin/news/create', '/admin/pages',
            '/admin/categories', '/admin/users', '/admin/files', '/admin/menus',
        ] as $адрес) {
            $this->get($адрес)->assertOk("Раздел {$адрес} не открылся у администратора");
        }
    }

    /** Материал создаётся, правится и удаляется из панели. */
    public function test_admin_manages_material(): void
    {
        $this->actingAs($this->админ());

        $this->post(route('admin.news.store'), [
            'title' => 'Созданный проверкой',
            'slug' => 'sozdannyy-proverkoy',
            'content' => '<p>Текст</p>',
            'template' => 'default',
            'published' => 1,
        ])->assertSessionHasNoErrors();

        $материал = News::where('slug', 'sozdannyy-proverkoy')->first();
        $this->assertNotNull($материал, 'Материал не создался');

        $this->put(route('admin.news.update', $материал->id), [
            'title' => 'Изменённый проверкой',
            'slug' => $материал->slug,
            'content' => '<p>Другой текст</p>',
            'template' => 'default',
            'published' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Изменённый проверкой', $материал->fresh()->title);

        $this->delete(route('admin.news.destroy', $материал->id));
        $this->assertNull(News::find($материал->id), 'Материал не удалился');
    }

    /** Страница создаётся из панели и открывается на сайте. */
    public function test_admin_creates_page_visible_on_site(): void
    {
        $this->actingAs($this->админ());

        $this->post(route('admin.pages.store'), [
            'title' => 'Страница проверки',
            'slug' => 'stranica-proverki',
            'content' => '<p>Текст страницы</p>',
            'published' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pages', ['slug' => 'stranica-proverki']);

        // Выходим и смотрим глазами гостя
        auth()->logout();
        $this->get('/page/stranica-proverki')->assertOk()->assertSee('Страница проверки', false);
    }

    /** Снятый с публикации материал исчезает с сайта. */
    public function test_unpublished_material_disappears(): void
    {
        $материал = $this->материал(['title' => 'Виден пока опубликован', 'slug' => 'viden-poka']);

        $this->get('/news/' . $материал->slug)->assertOk();

        $материал->update(['published' => false]);

        $ответ = $this->get('/news/' . $материал->slug);
        $this->assertContains(
            $ответ->getStatusCode(),
            [404, 302],
            'Неопубликованный материал остался доступен'
        );
    }

    // ───────────────────────────── СОДЕРЖИМОЕ ───────────────────────────

    /** Форма принимает заявку и сохраняет её. */
    public function test_form_accepts_submission(): void
    {
        Event::fake();

        $форма = \Modules\Forms\Models\Form::create([
            'title' => 'Проверочная форма',
            'slug' => 'proverochnaya',
            'fields' => [
                ['type' => 'text', 'name' => 'name', 'label' => 'Имя', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Почта', 'required' => true],
            ],
            'settings' => [],
            'is_active' => true,
        ]);

        // ⚠️ Значения полей приезжают в массиве `fields`, а не отдельными
        // ключами: так форма отличает свои поля от служебных (_return, каптча).
        $ответ = $this->post(route('forms.submit', $форма->slug), [
            'fields' => [
                'name' => 'Иван',
                'email' => 'ivan@example.com',
            ],
        ]);

        $this->assertContains($ответ->getStatusCode(), [200, 302],
            'Отправка формы вернула ' . $ответ->getStatusCode());

        $this->assertDatabaseCount('form_submissions', 1);
    }
}
