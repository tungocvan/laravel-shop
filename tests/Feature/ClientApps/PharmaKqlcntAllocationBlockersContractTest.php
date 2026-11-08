<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaKqlcntAllocationBlockersContractTest extends TestCase
{
    public function test_blockers_are_read_only_and_do_not_bypass_exact_scope_bridge(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntAllocationBlockers.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('$this->bridge->scopeKey($awardItemId)', $source);
        $this->assertStringContainsString('shared_tbmt_scope', $source);
        $this->assertStringContainsString('contractor_mismatch', $source);
        $this->assertStringContainsString('product_count_mismatch', $source);
        $this->assertStringContainsString('identity_unverified', $source);
        foreach (['->save(', '->update(', '->delete(', '->insert(', 'firstOrCreate(', 'updateOrCreate('] as $mutation) {
            $this->assertStringNotContainsString($mutation, $source);
        }
    }

    public function test_canonical_ui_exposes_blocker_without_enabling_post_forms(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-workflow-preview.blade.php'));
        $this->assertStringContainsString("'allocationBlockers' => app(KqlcntAllocationBlockers::class)->inspect(\$awardItem)", $controller);
        $this->assertStringContainsString("allocationBlockers['reason']", $view);
        $this->assertStringContainsString("allocationBlockers['status']", $view);
        $this->assertStringContainsString("kqlcnt-awards.mapping.confirm", $view);
    }
}
