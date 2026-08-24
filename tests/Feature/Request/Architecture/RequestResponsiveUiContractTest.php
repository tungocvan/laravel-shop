<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestResponsiveUiContractTest extends TestCase
{
    public function test_designer_has_keyboard_ordering_responsive_layout_and_version_review(): void
    {
        $designer = file_get_contents(base_path('Modules/Request/resources/views/livewire/admin/type-designer.blade.php'));
        $versions = file_get_contents(base_path('Modules/Request/resources/views/admin/versions.blade.php'));

        $this->assertStringContainsString('moveSection', $designer);
        $this->assertStringContainsString('moveField', $designer);
        $this->assertStringContainsString('moveStage', $designer);
        $this->assertStringContainsString('xl:grid-cols-[14rem_minmax(0,1fr)_18rem]', $designer);
        $this->assertStringContainsString('min-h-11', $designer);
        $this->assertStringContainsString('Review canonical definition details', $versions);
        $this->assertStringNotContainsString('draggable=', $designer);
    }

    public function test_request_detail_exposes_reviewed_local_draft_contract_without_mutation_replay(): void
    {
        $detail = file_get_contents(base_path('Modules/Request/resources/views/livewire/requester/request-detail.blade.php'));
        $runtime = file_get_contents(base_path('Modules/Request/resources/js/request-offline.js'));

        $this->assertStringContainsString('data-request-draft-form', $detail);
        $this->assertStringContainsString('data-request-offline-draft', $detail);
        $this->assertStringContainsString('data-request-restore-draft', $detail);
        $this->assertStringContainsString('server_lock_version', $runtime);
        $this->assertStringContainsString("setState('conflict')", $runtime);
        $this->assertStringNotContainsString('Background Sync', $runtime);
        $this->assertStringNotContainsString('.submit()', $runtime);
    }
}
