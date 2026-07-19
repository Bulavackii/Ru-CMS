<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SecurityService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class SecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SecurityService::class);
    }

    /** @test */
    public function it_can_generate_2fa_secret()
    {
        $secret = $this->service->generate2FASecret();

        $this->assertNotEmpty($secret);
        $this->assertIsString($secret);
        $this->assertGreaterThan(16, strlen($secret));
    }

    /** @test */
    public function it_can_verify_2fa_code()
    {
        $secret = $this->service->generate2FASecret();
        $google2fa = new Google2FA();
        $code = $google2fa->getCurrentOtp($secret);

        $result = $this->service->verify2FACode($secret, $code);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_2fa_code()
    {
        $secret = $this->service->generate2FASecret();
        $invalidCode = '000000';

        $result = $this->service->verify2FACode($secret, $invalidCode);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_check_login_attempts()
    {
        $identifier = 'test@example.com';

        // Первые 5 попыток должны быть разрешены
        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->checkLoginAttempts($identifier);
            $this->assertTrue($result);
            $this->service->incrementLoginAttempts($identifier);
        }

        // 6-я попытка должна быть заблокирована
        $result = $this->service->checkLoginAttempts($identifier);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_block_ip_address()
    {
        $ip = '192.168.1.100';
        
        $this->service->blockIp($ip, 60);

        $this->assertTrue($this->service->isIpBlocked($ip));
    }

    /** @test */
    public function it_can_unblock_ip_address()
    {
        $ip = '192.168.1.100';
        
        $this->service->blockIp($ip, 60);
        $this->assertTrue($this->service->isIpBlocked($ip));

        $this->service->unblockIp($ip);
        $this->assertFalse($this->service->isIpBlocked($ip));
    }

    /** @test */
    public function it_validates_password_strength()
    {
        // Слабый пароль
        $weakPassword = '12345';
        $result = $this->service->validatePasswordStrength($weakPassword);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);

        // Сильный пароль
        $strongPassword = 'StrongP@ssw0rd123';
        $result = $this->service->validatePasswordStrength($strongPassword);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_detects_sql_injection_attempts()
    {
        $maliciousInput = "'; DROP TABLE users; --";
        
        $result = $this->service->detectSqlInjection($maliciousInput);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_detects_xss_attempts()
    {
        $maliciousInput = '<script>alert("XSS")</script>';
        
        $result = $this->service->detectXss($maliciousInput);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_sanitize_input()
    {
        $maliciousInput = '<script>alert("XSS")</script>Hello';
        
        $result = $this->service->sanitizeInput($maliciousInput);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    /** @test */
    public function it_can_generate_secure_token()
    {
        $token = $this->service->generateSecureToken(32);

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex characters
    }
}

