<?php

declare(strict_types=1);

namespace Tests\Unit\Audit;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Audit\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new AuditLogger();
    }

    public function test_can_log_event_and_redact_sensitive_fields(): void
    {
        $user = User::factory()->create();

        $audit = $this->logger->log(
            event: 'user.password_changed',
            auditable: $user,
            oldValues: ['email' => 'test@example.com', 'password' => 'old_secret_pwd'],
            newValues: ['email' => 'test@example.com', 'password' => 'new_secret_pwd', 'token' => 'abc123secret'],
            userId: $user->id
        );

        $this->assertInstanceOf(AuditLog::class, $audit);
        $this->assertEquals($user->id, $audit->user_id);
        $this->assertEquals('user.password_changed', $audit->event);
        $this->assertEquals('test@example.com', $audit->old_values['email']);

        // Assert sensitive passwords and tokens are redacted
        $this->assertEquals('********', $audit->old_values['password']);
        $this->assertEquals('********', $audit->new_values['password']);
        $this->assertEquals('********', $audit->new_values['token']);
    }
}
