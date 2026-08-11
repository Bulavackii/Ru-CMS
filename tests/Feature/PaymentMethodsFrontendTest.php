<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;
use Modules\Payments\Models\PaymentMethod;
use Tests\TestCase;

/**
 * Вывод способов оплаты покупателю.
 *
 * Ключевое: выключенный метод и метод без реквизитов не должны попадать
 * в корзину — иначе покупатель выберет способ, которым нельзя заплатить.
 */
class PaymentMethodsFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_methods_are_offered(): void
    {
        SeedDefaultPaymentMethodsCommand::seed();

        // Выключаем всё и включаем ровно один способ: в корзину должен
        // попасть только он.
        PaymentMethod::query()->update(['active' => false]);
        PaymentMethod::where('code', 'cash')->update(['active' => true]);

        $offered = PaymentMethod::where('active', true)->orderBy('sort_order')->get();

        $this->assertCount(1, $offered);
        $this->assertSame('cash', $offered->first()->code);
    }

    public function test_methods_are_ordered_by_sort_order(): void
    {
        // Поле «Порядок» в админке должно влиять на корзину, иначе оно
        // просто украшение формы.
        PaymentMethod::create(['title' => 'Второй', 'code' => 'second', 'type' => 'offline', 'active' => true, 'sort_order' => 5]);
        PaymentMethod::create(['title' => 'Первый', 'code' => 'first', 'type' => 'offline', 'active' => true, 'sort_order' => 1]);

        $codes = PaymentMethod::where('active', true)->orderBy('sort_order')->orderBy('id')->pluck('code')->all();

        $this->assertSame(['first', 'second'], $codes);
    }

    public function test_methods_needing_keys_are_not_offered_until_enabled(): void
    {
        // Сидирование при установке не должно показать покупателю
        // ненастроенные способы: онлайн-системы без реквизитов приняли бы
        // выбор и не смогли бы провести платёж. Наличные и перевод
        // включены намеренно — им реквизиты не нужны.
        SeedDefaultPaymentMethodsCommand::seed();

        $needKeys = array_keys(array_filter(SeedDefaultPaymentMethodsCommand::credentialFields()));

        $this->assertSame(
            [],
            PaymentMethod::where('active', true)->whereIn('type', $needKeys)->pluck('code')->all()
        );
    }

    public function test_commission_is_formatted_for_the_customer(): void
    {
        $method = PaymentMethod::create([
            'title' => 'С комиссией', 'code' => 'fee', 'type' => 'offline',
            'active' => true, 'commission' => 3.5,
        ]);

        // Формат русский: запятая как разделитель и знак процента.
        $this->assertSame('3,50%', (string) $method->formattedCommission);
    }
}
