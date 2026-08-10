<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 🔐 Вход по двухфакторной проверке.
 *
 * Проверка обязана работать для ЛЮБОГО пользователя, а не только для
 * администратора: включить её может каждый из своего кабинета.
 */
class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    private Google2FA $totp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->totp = new Google2FA();
    }

    private function guarded(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make('secret-pass'),
            'two_factor_secret' => $this->totp->generateSecretKey(),
            'two_factor_enabled' => true,
        ], $attributes));
    }

    /**
     * Самое важное: пароль сам по себе внутрь НЕ пускает.
     *
     * Ровно это и было сломано — вход выполнялся до перехода на ввод кода,
     * а страница кода закрыта middleware `guest`, так что вошедшего с неё
     * уводило прямо на сайт. Код не спрашивался ни у кого.
     */
    #[Test]
    public function один_пароль_внутрь_не_пускает(): void
    {
        $user = $this->guarded();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-pass',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    #[Test]
    public function страница_ввода_кода_открывается_после_пароля(): void
    {
        $user = $this->guarded();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);

        $this->get(route('two-factor.login'))->assertOk();
    }

    #[Test]
    public function неверный_код_внутрь_не_пускает(): void
    {
        $user = $this->guarded();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);

        $this->post(route('two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    #[Test]
    public function обычный_пользователь_входит_по_верному_коду(): void
    {
        $user = $this->guarded(['is_admin' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);

        $response = $this->post(route('two-factor.verify'), [
            'code' => $this->totp->getCurrentOtp($user->two_factor_secret),
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function администратор_входит_по_верному_коду_и_попадает_в_панель(): void
    {
        $user = $this->guarded(['is_admin' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);

        $response = $this->post(route('two-factor.verify'), [
            'code' => $this->totp->getCurrentOtp($user->two_factor_secret),
        ]);

        // Тот же адрес, что и при обычном входе администратора: главная
        // панели, а не «Модули» — расхождение было прямо между двумя
        // контроллерами одного и того же входа.
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Коды восстановления генерировались, показывались и хранились — но не
     * принимались нигде. Потерянный телефон означал потерю доступа навсегда.
     */
    #[Test]
    public function код_восстановления_пускает_и_сгорает(): void
    {
        $user = $this->guarded(['two_factor_recovery_codes' => ['AAAA111111', 'BBBB222222']]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);
        $this->post(route('two-factor.verify'), ['code' => 'AAAA111111'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(['BBBB222222'], $user->fresh()->two_factor_recovery_codes);

        // Второй раз тот же код не работает: подсмотренный листок с кодами
        // иначе пускал бы внутрь сколько угодно раз.
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);
        $this->post(route('two-factor.verify'), ['code' => 'AAAA111111'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    #[Test]
    public function коды_восстановления_не_повторяются_и_имеют_нужный_вид(): void
    {
        $codes = (new \ReflectionMethod(
            \App\Http\Controllers\Auth\TwoFactorSetupController::class,
            'generateRecoveryCodes'
        ))->invoke(app(\App\Http\Controllers\Auth\TwoFactorSetupController::class));

        $this->assertCount(8, $codes);
        $this->assertSame($codes, array_unique($codes));

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('~^[0-9A-Z]{10}$~', $code);
        }
    }

    /**
     * Обход через API: на сайте пароля мало, а по нему выдавался постоянный
     * токен с полным доступом.
     */
    #[Test]
    public function апи_не_выдаёт_токен_по_одному_паролю(): void
    {
        $user = $this->guarded();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-pass',
        ])->assertStatus(403);

        $this->assertGuest();
        $this->assertSame(0, $user->tokens()->count());
    }

    /**
     * Тип возврата у `show()` был объявлен как View, а ветка без начатого
     * входа возвращала редирект — заход на адрес отдавал 500.
     */
    #[Test]
    public function страница_кода_без_начатого_входа_возвращает_на_форму(): void
    {
        $this->get(route('two-factor.login'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');
    }

    #[Test]
    public function на_форме_входа_есть_ссылка_на_шаг_с_кодом(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('two-factor.login'), false)
            ->assertSee(__('frontend.auth.tfa_entry'), false);
    }

    #[Test]
    public function при_незавершённом_входе_ссылка_предлагает_продолжить(): void
    {
        $user = $this->guarded();

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass']);

        $this->get(route('login'))
            ->assertSee(__('frontend.auth.tfa_continue'), false);
    }

    #[Test]
    public function без_двухфакторной_проверки_вход_как_прежде(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret-pass'),
            'two_factor_enabled' => false,
            'is_admin' => false,
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }
}
