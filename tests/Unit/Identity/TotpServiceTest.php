<?php

declare(strict_types=1);

namespace Tests\Unit\Identity;

use App\Modules\Identity\Services\TotpService;
use Tests\TestCase;

class TotpServiceTest extends TestCase
{
    protected TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = new TotpService();
    }

    public function test_can_generate_valid_base32_secret(): void
    {
        $secret = $this->totp->generateSecret(32);
        $this->assertEquals(32, strlen($secret));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function test_can_calculate_and_verify_totp_code(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP'; // Standard RFC 3548 test vector
        $code = $this->totp->calculateCode($secret);

        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $isValid = $this->totp->verifyCode($secret, $code);
        $this->assertTrue($isValid);

        // Verification of incorrect code fails
        $this->assertFalse($this->totp->verifyCode($secret, '999999'));
    }

    public function test_can_generate_recovery_codes(): void
    {
        $codes = $this->totp->generateRecoveryCodes(8);
        $this->assertCount(8, $codes);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{8}-[A-F0-9]{8}$/', $codes[0]);
    }
}
