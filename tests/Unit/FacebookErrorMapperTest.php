<?php

namespace Tests\Unit;

use Modules\Facebook\Exceptions\FacebookAuthenticationException;
use Modules\Facebook\Exceptions\FacebookRateLimitException;
use Modules\Facebook\Services\FacebookErrorMapper;
use PHPUnit\Framework\TestCase;

class FacebookErrorMapperTest extends TestCase
{
    public function test_it_maps_invalid_token_error(): void
    {
        $exception = (new FacebookErrorMapper)->fromResponse(400, [
            'error' => [
                'code' => 190,
                'message' => 'Invalid OAuth access token.',
            ],
        ]);

        $this->assertInstanceOf(FacebookAuthenticationException::class, $exception);
        $this->assertFalse($exception->retryable());
    }

    public function test_it_maps_rate_limit_as_retryable(): void
    {
        $exception = (new FacebookErrorMapper)->fromResponse(429, [
            'error' => [
                'code' => 4,
                'message' => 'Application request limit reached.',
            ],
        ]);

        $this->assertInstanceOf(FacebookRateLimitException::class, $exception);
        $this->assertTrue($exception->retryable());
    }
}
