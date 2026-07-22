<?php

namespace Tests\Feature;

use Tests\TestCase;
use Modules\Localization\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Регрессия: вьюхи admin/{index,create,edit,settings}.blade.php модуля Localization
 * расширяли несуществующий 'System::Views.admin.modules' (лишний сегмент "Views."
 * в пути неймспейса — namespace 'System' указывает на modules/System/Views,
 * поэтому корректная ссылка — 'System::admin.modules', а на деле файл вообще не
 * задумывался как переиспользуемый лейаут). Из-за этого любой запрос к
 * /admin/localization падал с 500 (View not found). Починка — @extends('layouts.admin').
 */
class LocalizationAdminViewsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_view_localization_index(): void
    {
        Country::create([
            'code' => 'RU',
            'name' => 'Россия',
            'currency_code' => 'RUB',
            'locale' => 'ru_RU',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.localization.index'));

        $response->assertStatus(200);
        $response->assertViewIs('Localization::admin.index');
    }

    public function test_admin_can_view_localization_create(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.localization.create'));

        $response->assertStatus(200);
        $response->assertViewIs('Localization::admin.create');
    }

    public function test_admin_can_view_localization_edit(): void
    {
        $country = Country::create([
            'code' => 'RU',
            'name' => 'Россия',
            'currency_code' => 'RUB',
            'locale' => 'ru_RU',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.localization.edit', $country->code));

        $response->assertStatus(200);
        $response->assertViewIs('Localization::admin.edit');
    }

    public function test_admin_can_view_localization_settings(): void
    {
        $country = Country::create([
            'code' => 'RU',
            'name' => 'Россия',
            'currency_code' => 'RUB',
            'locale' => 'ru_RU',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.localization.settings', $country->code));

        $response->assertStatus(200);
        $response->assertViewIs('Localization::admin.settings');
    }
}
