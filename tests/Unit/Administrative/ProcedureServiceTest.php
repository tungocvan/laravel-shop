<?php

namespace Tests\Unit\Administrative;

use Modules\Administrative\Services\AdministrativeFileService;
use Modules\Administrative\Services\ProcedureService;
use Modules\Administrative\Services\SubmissionService;
use PHPUnit\Framework\TestCase;

class ProcedureServiceTest extends TestCase
{
    public function test_it_normalizes_an_explicit_slug(): void
    {
        $this->assertSame('cap-lai-bang-diem', (new ProcedureService)->normalizeSlug(' Cấp lại bảng điểm ', 'Khác'));
    }

    public function test_it_falls_back_to_the_procedure_name(): void
    {
        $this->assertSame('chuyen-lop', (new ProcedureService)->normalizeSlug(null, 'Chuyển lớp'));
    }

    public function test_submission_code_uses_date_and_zero_padded_id(): void
    {
        $service = new SubmissionService(new AdministrativeFileService);

        $this->assertSame('HC-20260806-00015', $service->formatSubmissionCode(15, new \DateTimeImmutable('2026-08-06')));
    }

    public function test_temporary_submission_code_fits_database_column(): void
    {
        $service = new SubmissionService(new AdministrativeFileService);
        $code = $service->temporarySubmissionCode();

        $this->assertStringStartsWith('TMP-', $code);
        $this->assertLessThanOrEqual(32, strlen($code));
    }
}
