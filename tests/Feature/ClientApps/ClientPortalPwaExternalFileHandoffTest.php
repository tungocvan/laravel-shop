<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class ClientPortalPwaExternalFileHandoffTest extends TestCase
{
    public function test_muasamcong_price_list_hands_excel_and_pdf_to_external_context_in_installed_pwa(): void
    {
        $polish = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/muasamcong/partials/price-list-workspace-polish.blade.php'));
        $handoff = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/muasamcong/partials/external-file-handoff.blade.php'));
        $manifest = require base_path('Modules/ClientPortal/Applications/Muasamcong/manifest.php');

        $this->assertStringContainsString("window.matchMedia('(display-mode: standalone)').matches", $polish);
        $this->assertStringContainsString('window.navigator.standalone === true', $polish);
        $this->assertStringContainsString('preservePwaContextForFile(excel);', $polish);
        $this->assertStringContainsString('preservePwaContextForFile(pdfDownload);', $polish);

        $this->assertStringContainsString("document.addEventListener('click'", $handoff);
        $this->assertStringContainsString("event.target.closest('a[data-pwa-file-handoff]')", $handoff);
        $this->assertStringContainsString('event.stopImmediatePropagation();', $handoff);
        $this->assertStringContainsString("window.open(link.href, '_blank', 'noopener')", $handoff);
        $this->assertStringNotContainsString("document.createElement('iframe')", $handoff);

        $scriptViews = array_column($manifest['shell_extensions']['scripts'], 'view');
        $this->assertContains('ClientPortal::applications.muasamcong.partials.external-file-handoff', $scriptViews);
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
