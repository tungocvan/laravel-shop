<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class ClientPortalPwaExternalFileHandoffTest extends TestCase
{
    public function test_muasamcong_price_list_preserves_installed_pwa_context_for_excel_and_pdf_downloads(): void
    {
        $polish = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/muasamcong/partials/price-list-workspace-polish.blade.php'));

        $this->assertStringContainsString("window.matchMedia('(display-mode: standalone)').matches", $polish);
        $this->assertStringContainsString('window.navigator.standalone === true', $polish);
        $this->assertStringContainsString('const preservePwaContextForFile = (link) => {', $polish);
        $this->assertStringContainsString("link.dataset.pwaFileHandoff = '1'", $polish);
        $this->assertStringContainsString('event.preventDefault();', $polish);
        $this->assertStringContainsString("document.createElement('iframe')", $polish);
        $this->assertStringContainsString('frame.src = link.href;', $polish);
        $this->assertStringContainsString('preservePwaContextForFile(excel);', $polish);
        $this->assertStringContainsString('preservePwaContextForFile(pdfDownload);', $polish);
    }

    public function test_project_workflow_requires_pwa_file_handoff_review_for_all_modules(): void
    {
        $workflow = file_get_contents(base_path('docs/GITHUB_COLLABORATION_WORKFLOW.md'));
        $contract = file_get_contents(base_path('docs/PWA_EXTERNAL_FILE_HANDOFF.md'));

        $this->assertStringContainsString('### 10.2 PWA download/open file gate bắt buộc', $workflow);
        $this->assertStringContainsString('docs/PWA_EXTERNAL_FILE_HANDOFF.md', $workflow);
        $this->assertStringContainsString('PWA file handoff acceptance', $workflow);

        $this->assertStringContainsString('An installed PWA must not use top-level navigation', $contract);
        $this->assertStringContainsString('same-origin authenticated files', $contract);
        $this->assertStringContainsString('iOS installed PWA', $contract);
        $this->assertStringContainsString('Android installed PWA', $contract);
        $this->assertStringContainsString('Desktop / normal browser', $contract);
    }
}
