<?php

namespace Modules\Facebook\Tests\Unit;

use Modules\Facebook\Services\FacebookTokenMasker;
use PHPUnit\Framework\TestCase;

class FacebookTokenMaskerTest extends TestCase
{
    public function test_it_masks_long_tokens(): void
    {
        $masked = (new FacebookTokenMasker)->mask('EAAJ1234567890xYz1');

        $this->assertSame('EAAJ**************xYz1', $masked);
    }

    public function test_empty_token_returns_dash(): void
    {
        $this->assertSame('-', (new FacebookTokenMasker)->mask(null));
    }
}
