<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Экраны входа, регистрации и восстановления пароля.
 *
 * Отдельно закреплено то, что раньше молча терялось: форма регистрации
 * спрашивала реквизиты организации полями, которых нет ни в схеме, ни в
 * контроллере, а модель вдобавок не пускала их через $fillable. Человек
 * заполнял анкету, видел «зарегистрировано», а реквизиты никуда не попадали.
 */
class AuthScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_screens_open(): void
    {
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login');
        $this->get(route('register'))->assertOk()->assertViewIs('auth.register');
        $this->get(route('password.request'))->assertOk()->assertViewIs('auth.forgot-password');
    }

    public function test_auth_screens_take_colours_from_the_active_theme(): void
    {
        // Экраны жили на своём лейауте и о теме не знали вовсе: оставались
        // синими, когда весь остальной сайт менял цвет.
        $html = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('--color-primary', $html, 'Переменные темы не подключены');
        $this->assertStringContainsString('var(--color-primary', $html, 'Оформление не следует за темой');
    }

    public function test_individual_registration_stores_the_basics(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Иван Петров',
            'email'                 => 'ivan@example.com',
            'phone'                 => '+7 900 000-00-00',
            'is_company'            => '0',
            'password'              => 'Very$trongPass99',
            'password_confirmation' => 'Very$trongPass99',
            'terms_agree'           => '1',
        ])->assertRedirect();

        $user = User::where('email', 'ivan@example.com')->first();

        $this->assertNotNull($user, 'Пользователь не создан');
        // Телефон нормализуется мутатором модели: пробелы и дефисы убираются,
        // чтобы один и тот же номер не лежал в базе в трёх записях.
        $this->assertSame('+79000000000', $user->phone);
        $this->assertFalse((bool) $user->is_company);
    }

    public function test_company_details_are_actually_saved(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Пётр Сидоров',
            'email'                 => 'petr@example.com',
            'is_company'            => '1',
            'company_name'          => 'ООО «Ромашка»',
            'inn'                   => '7701234567',
            'ogrn'                  => '1027700132195',
            'password'              => 'Very$trongPass99',
            'password_confirmation' => 'Very$trongPass99',
            'terms_agree'           => '1',
        ])->assertRedirect();

        $user = User::where('email', 'petr@example.com')->first();

        $this->assertNotNull($user, 'Пользователь не создан');
        $this->assertTrue((bool) $user->is_company);
        $this->assertSame('ООО «Ромашка»', $user->company_name, 'Название организации потеряно');
        $this->assertSame('7701234567', $user->inn, 'ИНН потерян');
        $this->assertSame('1027700132195', $user->ogrn, 'ОГРН потерян');
    }

    public function test_company_registration_requires_its_details(): void
    {
        // Организация без названия и ИНН — не организация: без проверки в базе
        // оседала бы пустая карточка юрлица.
        $this->post(route('register'), [
            'name'                  => 'Пётр Сидоров',
            'email'                 => 'petr2@example.com',
            'is_company'            => '1',
            'password'              => 'Very$trongPass99',
            'password_confirmation' => 'Very$trongPass99',
            'terms_agree'           => '1',
        ])->assertSessionHasErrors(['company_name', 'inn']);

        $this->assertDatabaseMissing('users', ['email' => 'petr2@example.com']);
    }

    public function test_registration_requires_accepting_the_terms(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Без согласия',
            'email'                 => 'no-terms@example.com',
            'is_company'            => '0',
            'password'              => 'Very$trongPass99',
            'password_confirmation' => 'Very$trongPass99',
        ])->assertSessionHasErrors('terms_agree');

        $this->assertDatabaseMissing('users', ['email' => 'no-terms@example.com']);
    }
}
