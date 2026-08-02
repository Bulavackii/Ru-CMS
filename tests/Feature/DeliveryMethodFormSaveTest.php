<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Delivery\Console\Commands\SeedDefaultDeliveryMethodsCommand;
use Modules\Delivery\Models\DeliveryMethod;
use Tests\TestCase;

/**
 * Сохранение формы способа доставки и проверка связи со службой.
 *
 * Тест написан сразу после переработки формы: на Оплате ровно в этот
 * момент три поля молча перестали сохраняться, потому что правил
 * валидации для них не было и validated() их выбрасывал.
 */
class DeliveryMethodFormSaveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'СДЭК до пункта',
            'code' => 'cdek',
            'type' => 'courier',
            'description' => 'Выдача в пункте СДЭК',
            'price' => 400,
            'free_delivery_threshold' => 5000,
            'min_days' => 2,
            'max_days' => 7,
            'weight_limit' => 30,
            'sort_order' => 4,
            'docs_url' => 'https://api-docs.cdek.ru/',
            'api_enabled' => 1,
            'api_settings' => ['account' => 'acc', 'secure_password' => 'pw'],
            'regions' => [DeliveryMethod::ALL_REGIONS],
            'active' => 1,
            'is_russian' => 1,
        ], $override);
    }

    public function test_form_saves_every_field(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.delivery.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $method = DeliveryMethod::where('code', 'cdek')->first();

        $this->assertNotNull($method);
        $this->assertSame(4, (int) $method->sort_order);
        $this->assertSame('https://api-docs.cdek.ru/', $method->docs_url);
        $this->assertSame('acc', $method->api_settings['account']);
        $this->assertSame(30.0, (float) $method->weight_limit);
        $this->assertSame(5000.0, (float) $method->free_delivery_threshold);
        $this->assertTrue($method->api_enabled);
    }

    public function test_regions_keep_the_identifier_not_a_translation(): void
    {
        // Значение option — идентификатор в базе. Если бы оно переводилось,
        // на другой локали в regions уехала бы другая строка.
        $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'en'])
            ->post(route('admin.delivery.store'), $this->payload(['code' => 'boxberry']))
            ->assertSessionHasNoErrors();

        $method = DeliveryMethod::where('code', 'boxberry')->first();

        $this->assertSame([DeliveryMethod::ALL_REGIONS], $method->regions);
        $this->assertTrue($method->isAvailableInRegion('Курск'));
    }

    public function test_update_keeps_the_new_fields(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();
        $method = DeliveryMethod::where('code', 'cdek')->first();

        $this->actingAs($this->admin())
            ->put(route('admin.delivery.update', $method->id), $this->payload(['sort_order' => 9]))
            ->assertSessionHasNoErrors();

        $method->refresh();

        $this->assertSame(9, (int) $method->sort_order);
        $this->assertTrue($method->active);
    }

    public function test_invalid_docs_url_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.delivery.store'), $this->payload(['docs_url' => 'не ссылка']))
            ->assertSessionHasErrors('docs_url');
    }

    public function test_max_days_cannot_be_less_than_min_days(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.delivery.store'), $this->payload(['min_days' => 10, 'max_days' => 2]))
            ->assertSessionHasErrors('max_days');
    }

    public function test_check_reports_missing_keys_before_any_request(): void
    {
        Http::fake();

        SeedDefaultDeliveryMethodsCommand::seed();
        $method = DeliveryMethod::where('code', 'cdek')->first();

        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->post(route('admin.delivery.check', $method->id));

        $response->assertStatus(422);
        $this->assertFalse($response->json('ok'));
        Http::assertNothingSent();
    }

    public function test_check_says_nothing_to_verify_for_pickup(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();
        $method = DeliveryMethod::where('code', 'pickup')->first();

        $response = $this->actingAs($this->admin())
            ->withSession(['app_locale' => 'ru'])
            ->post(route('admin.delivery.check', $method->id));

        $response->assertStatus(200);
        $this->assertTrue($response->json('ok'));
    }

    public function test_check_is_closed_for_plain_users(): void
    {
        SeedDefaultDeliveryMethodsCommand::seed();
        $method = DeliveryMethod::where('code', 'pickup')->first();

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->post(route('admin.delivery.check', $method->id))
            ->assertForbidden();
    }
}
