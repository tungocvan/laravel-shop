<?php

namespace Tests\Feature\Muasamcong;

use Tests\TestCase;

class KqlcntExcelPublishedAtMidnightContractTest extends TestCase
{
    public function test_date_only_published_at_defaults_to_midnight_without_overriding_explicit_time(): void
    {
        $service = app(\Modules\Muasamcong\Services\KqlcntAwardExcelImportService::class);
        $method = new \ReflectionMethod($service, 'normalizeValue');

        $errors = [];
        $this->assertSame('09/11/2026 00:00', $method->invokeArgs($service, ['published_at', '09/11/2026', &$errors]));
        $this->assertSame([], $errors);

        $errors = [];
        $this->assertSame('09/11/2026 08:30', $method->invokeArgs($service, ['published_at', '09/11/2026 08:30', &$errors]));
        $this->assertSame([], $errors);

        $errors = [];
        $this->assertSame('09/11/2026 00:00', $method->invokeArgs($service, ['published_at', new \DateTimeImmutable('2026-11-09 00:00:00'), &$errors]));
        $this->assertSame([], $errors);
    }

    public function test_invalid_calendar_date_is_still_rejected(): void
    {
        $service = app(\Modules\Muasamcong\Services\KqlcntAwardExcelImportService::class);
        $method = new \ReflectionMethod($service, 'normalizeValue');
        $errors = [];
        $method->invokeArgs($service, ['published_at', '31/02/2026', &$errors]);
        $this->assertNotEmpty($errors);
    }
}
