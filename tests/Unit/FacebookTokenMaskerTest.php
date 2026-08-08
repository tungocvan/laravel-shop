<?php

namespace Tests\Unit;

use Modules\Facebook\Services\FacebookTokenMasker;
use PHPUnit\Framework\TestCase;

class FacebookTokenMaskerTest extends TestCase
{
    public function test_it_masks_long_tokens(): void
    {
        $this->assertSame(
            'EAAJ**************xYz1',
            (new FacebookTokenMasker)->mask('EAAJ1234567890xYz1')
        );
    }
}
