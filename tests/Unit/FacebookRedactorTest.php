<?php

namespace Tests\Unit;

use Modules\Facebook\Services\FacebookRedactor;
use PHPUnit\Framework\TestCase;

class FacebookRedactorTest extends TestCase
{
    public function test_it_redacts_sensitive_keys_recursively(): void
    {
        $redacted = (new FacebookRedactor)->redact([
            'access_token' => 'secret',
            'nested' => [
                'client_secret' => 'secret',
                'message' => 'safe',
            ],
        ]);

        $this->assertSame('[redacted]', $redacted['access_token']);
        $this->assertSame('[redacted]', $redacted['nested']['client_secret']);
        $this->assertSame('safe', $redacted['nested']['message']);
    }
}
