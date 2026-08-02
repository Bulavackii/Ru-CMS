<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Сохранение формы способа оплаты.
 *
 * Тестов на саму отправку формы не было, и после переработки формы три
 * поля молча перестали сохраняться: тип «Т-Банк» не проходил валидацию,
 * валюты приходили строкой вместо массива, а sort_order и docs_url
 * вообще отсутствовали в правилах и выбрасывались validated().
 */
class PaymentMethodFormSaveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Оплата картой',
            'type' => 'yookassa',
            'code' => 'test_card',
            'commission' => 2.5,
            'currencies' => 'RUB, USD',
            'sort_order' => 7,
            'docs_url' => 'https://yookassa.ru/developers',
            'settings' => ['shop_id' => '123', 'secret_key' => 'k'],
            'active' => 1,
            'test_mode' => 1,
            'is_russian' => 1,
        ], $override);
    }

    public function test_form_saves_every_field(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.payments.store'), $this->payload())
            ->assertRedirect(route('admin.payments.index'));

        $method = PaymentMethod::where('code', 'test_card')->first();

        $this->assertNotNull($method);
        $this->assertSame(7, (int) $method->sort_order);
        $this->assertSame('https://yookassa.ru/developers', $method->docs_url);
        $this->assertSame(['RUB', 'USD'], $method->currencies);
        $this->assertSame('123', $method->settings['shop_id']);
    }

    public function test_tbank_type_is_accepted(): void
    {
        // Форма предлагает Т-Банк, а список допустимых типов знал только
        // старое имя tinkoff — сохранить такой метод было нельзя.
        $this->actingAs($this->admin())
            ->post(route('admin.payments.store'), $this->payload([
                'type' => 'tbank',
                'code' => 'tbank_test',
                'settings' => ['terminal_key' => 't', 'password' => 'p'],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('tbank', PaymentMethod::where('code', 'tbank_test')->value('type'));
    }

    public function test_currencies_come_from_a_comma_separated_string(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.payments.store'), $this->payload([
                'code' => 'cur_test',
                'currencies' => 'rub,  eur ',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(['RUB', 'EUR'], PaymentMethod::where('code', 'cur_test')->first()->currencies);
    }

    public function test_update_keeps_the_new_fields(): void
    {
        $method = PaymentMethod::create([
            'title' => 'Старый', 'code' => 'old', 'type' => 'offline', 'active' => false,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.payments.update', $method->id), $this->payload([
                'code' => 'old',
                'sort_order' => 3,
            ]))
            ->assertSessionHasNoErrors();

        $method->refresh();

        $this->assertSame(3, (int) $method->sort_order);
        $this->assertTrue((bool) $method->active);
    }

    public function test_invalid_docs_url_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.payments.store'), $this->payload([
                'code' => 'bad_url',
                'docs_url' => 'не ссылка',
            ]))
            ->assertSessionHasErrors('docs_url');
    }
}
