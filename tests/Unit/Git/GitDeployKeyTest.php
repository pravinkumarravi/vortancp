<?php

declare(strict_types=1);

namespace Tests\Unit\Git;

use App\Modules\Git\Services\GitDeployKeyService;
use Tests\TestCase;

class GitDeployKeyTest extends TestCase
{
    protected GitDeployKeyService $keyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyService = new GitDeployKeyService();
    }

    public function test_generates_valid_ssh_key_pair(): void
    {
        $keyPair = $this->keyService->generateKeyPair(comment: 'test-user@vortan');

        $this->assertArrayHasKey('public', $keyPair);
        $this->assertArrayHasKey('private', $keyPair);

        $this->assertStringStartsWith('ssh-rsa ', $keyPair['public']);
        $this->assertStringEndsWith(' test-user@vortan', $keyPair['public']);
        $this->assertStringContainsString('PRIVATE KEY', $keyPair['private']);
    }
}
