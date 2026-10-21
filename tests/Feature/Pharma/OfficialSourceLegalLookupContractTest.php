<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class OfficialSourceLegalLookupContractTest extends TestCase
{
    public function test_lookup_uses_queue_and_partner_service_without_mutating_bhxh_or_partner(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/OfficialSourceLegalLookupController.php'));
        $job = file_get_contents(base_path('Modules/Pharma/Jobs/LookupOfficialSourceLegalJob.php'));
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $migration = file_get_contents(base_path('database/migrations/2026_10_21_000001_create_pharma_official_source_legal_lookups_table.php'));

        $this->assertStringContainsString('LookupOfficialSourceLegalJob::dispatch(', $controller);
        $this->assertStringContainsString('->afterCommit()', $controller);
        $this->assertStringContainsString('DoanhNghiepLookupService $service', $job);
        $this->assertStringContainsString('->search($lookup->query_name)', $job);
        $this->assertStringContainsString("'status' => 'reviewed'", $controller);
        $this->assertStringContainsString('selected_tax_code', $controller);
        $this->assertStringContainsString('can:sync_pharma_official_facilities', $routes);
        $this->assertStringContainsString('can:view_pharma_official_facilities', $routes);
        $this->assertStringContainsString("Schema::create('pharma_official_source_legal_lookups'", $migration);
        $this->assertStringNotContainsString('Partner::create(', $controller);
        $this->assertStringNotContainsString('OfficialSourceFacility::query()->update(', $job);
    }
}
