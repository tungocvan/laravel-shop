<?php

namespace Tests\Feature\Admission;

use Modules\Admission\Exports\ApplicationsExport;
use Modules\Admission\Services\AdmissionApplicationAdminService;
use Tests\TestCase;

class AdmissionExportFiltersTest extends TestCase
{
    public function test_export_service_passes_scalar_filters_not_query_builder(): void
    {
        $source = file_get_contents(base_path('Modules/Admission/Services/AdmissionApplicationAdminService.php'));
        $this->assertStringNotContainsString('new ApplicationsExport($this->query($filters))', $source);
        $this->assertStringContainsString("trim((string) (\$filters['search'] ?? ''))", $source);
        $this->assertStringContainsString("(string) (\$filters['status'] ?? '')", $source);
        $this->assertStringContainsString("(string) (\$filters['class'] ?? '')", $source);
    }

    public function test_export_query_accepts_empty_and_nonempty_filters(): void
    {
        $unfiltered = (new ApplicationsExport('', '', ''))->query()->toSql();
        $filtered = (new ApplicationsExport('An', 'pending', 'Lớp thường'))->query();

        $this->assertStringNotContainsString('like', strtolower($unfiltered));
        $this->assertStringContainsString('like', strtolower($filtered->toSql()));
        $this->assertContains('%An%', $filtered->getBindings());
        $this->assertContains('pending', $filtered->getBindings());
        $this->assertContains('Lớp thường', $filtered->getBindings());
    }
}
