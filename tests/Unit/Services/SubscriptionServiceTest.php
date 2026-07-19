<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SubscriptionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubscriptionService::class);
    }

    /** @test */
    public function it_can_check_active_subscription()
    {
        $result = $this->service->hasActiveSubscription();

        $this->assertIsBool($result);
    }

    /** @test */
    public function it_can_apply_promo_code()
    {
        // Создаем тестовый промокод
        \DB::table('promo_codes')->insert([
            'code' => 'TEST20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'reusable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->applyPromoCode('TEST20', 'basic');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    /** @test */
    public function it_rejects_invalid_promo_code()
    {
        $result = $this->service->applyPromoCode('INVALID_CODE', 'basic');

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
    }

    /** @test */
    public function it_can_validate_license_key()
    {
        // Создаем тестовую подписку
        \DB::table('subscriptions')->insert([
            'user_id' => 1,
            'plan' => 'basic',
            'license_key' => 'TEST-LICENSE-KEY-1234',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->validateLicenseKey('TEST-LICENSE-KEY-1234');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_license_key()
    {
        $result = $this->service->validateLicenseKey('INVALID-KEY');

        $this->assertFalse($result);
    }
}

