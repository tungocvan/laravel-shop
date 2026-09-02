<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestRefactorModuleContractTest extends TestCase
{
    public function test_module_contract_records_canonical_request_ownership_and_refactor_invariants(): void
    {
        $contract = file_get_contents(base_path('docs/modules/Request/MODULE.md'));

        foreach ([
            'Request — Module Contract',
            'Canonical Ownership',
            'Explicit Non-Ownership',
            'Persistence Ownership',
            'Selected export semantics are canonical',
            'no selected IDs → export all records in the approved current filter scope',
            'selected IDs present → export only those selected records that are still inside the approved authorization scope',
            'local/testing demo seeders must be environment-safe',
            '`REHOME`: none approved',
            '`DELETE`: none approved',
        ] as $contractStatement) {
            $this->assertStringContainsString($contractStatement, $contract);
        }
    }

    public function test_local_demo_seeders_cover_multiple_pages_and_all_request_statuses_without_production_enablement(): void
    {
        $rootSeeder = file_get_contents(base_path('Modules/Request/Database/Seeders/RequestDemoSeeder.php'));
        $workflowSeeder = file_get_contents(base_path('Modules/Request/Database/Seeders/RequestWorkflowDemoSeeder.php'));

        $this->assertStringContainsString('RequestWorkflowDemoSeeder::class', $rootSeeder);
        $this->assertStringContainsString("app()->environment(['local', 'testing'])", $workflowSeeder);
        $this->assertStringContainsString('$rowCount = 42', $workflowSeeder);
        $this->assertStringContainsString('RequestStatus::cases()', $workflowSeeder);
        $this->assertStringContainsString("'request_number' => sprintf('REQ-DEMO-%04d', \$index)", $workflowSeeder);
        $this->assertStringContainsString('firstOrNew', $workflowSeeder);
        $this->assertStringNotContainsString("environment('production')", $workflowSeeder);
    }
}
