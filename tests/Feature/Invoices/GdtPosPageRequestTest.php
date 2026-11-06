<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Services\GdtPosPageRequest;
use PHPUnit\Framework\TestCase;

class GdtPosPageRequestTest extends TestCase
{
    public function test_page_one_contains_captcha_but_no_state(): void
    {
        $query = GdtPosPageRequest::query('tdlap=ge=01/09/2026T00:00:00;ttxly==8', null, [
            'ckey' => 'first-key', 'cvalue' => 'first-answer',
        ]);

        $this->assertSame(15, $query['size']);
        $this->assertSame('tdlap:desc', $query['sort']);
        $this->assertArrayNotHasKey('state', $query);
        $this->assertSame('first-key', $query['ckey']);
    }

    public function test_page_two_uses_cursor_and_distinct_captcha(): void
    {
        $first = GdtPosPageRequest::query('ttxly==8', null, [
            'ckey' => 'first-key', 'cvalue' => 'first-answer',
        ]);
        $secondCaptcha = ['ckey' => 'second-key', 'cvalue' => 'second-answer'];
        $second = GdtPosPageRequest::query('ttxly==8', 'opaque-cursor', $secondCaptcha);

        $this->assertSame('opaque-cursor', $second['state']);
        $this->assertSame('second-key', $second['ckey']);
        $this->assertSame('second-answer', $second['cvalue']);
        $this->assertNotSame($first['ckey'], $second['ckey']);
        $this->assertSame(
            ['ckey' => 'second-key', 'cvalue' => 'second-answer'],
            GdtPosPageRequest::captchaHeaders($secondCaptcha)
        );
    }

    public function test_missing_fresh_captcha_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GdtPosPageRequest::query('ttxly==8', 'opaque-cursor', []);
    }

    public function test_regular_search_cannot_use_pos_request_contract(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GdtPosPageRequest::query('tdlap=ge=01/09/2026T00:00:00', null, [
            'ckey' => 'key', 'cvalue' => 'answer',
        ]);
    }
}
