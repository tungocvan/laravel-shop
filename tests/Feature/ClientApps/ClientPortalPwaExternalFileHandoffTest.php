<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class ClientPortalPwaExternalFileHandoffTest extends TestCase
{
    public function test_muasamcong_price_list_uses_native_file_share_without_replacing_installed_pwa(): void
    {
        $polish = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/muasamcong/partials/price-list-workspace-polish.blade.php'));
        $handoff = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/muasamcong/partials/external-file-handoff.blade.php'));
        $manifest = require base_path('Modules/ClientPortal/Applications/Muasamcong/manifest.php');

        $this->assertStringContainsString("excel.dataset.pwaFileHandoff = '1';", $polish);
        $this->assertStringContainsString("pdfDownload.dataset.pwaFileHandoff = '1';", $polish);
        $this->assertStringNotContainsString("document.createElement('iframe')", $polish);
        $this->assertStringNotContainsString('preservePwaContextForFile', $polish);

        $this->assertStringContainsString("window.matchMedia('(display-mode: standalone)').matches", $handoff);
        $this->assertStringContainsString('window.navigator.standalone === true', $handoff);
        $this->assertStringContainsString("event.target.closest('a[data-pwa-file-handoff]')", $handoff);
        $this->assertStringContainsString("credentials: 'same-origin'", $handoff);
        $this->assertStringContainsString("cache: 'no-store'", $handoff);
        $this->assertStringContainsString('const blob = await response.blob();', $handoff);
        $this->assertStringContainsString('const file = new File([blob], filename', $handoff);
        $this->assertStringContainsString('navigator.canShare(shareData)', $handoff);
        $this->assertStringContainsString('await navigator.share(shareData);', $handoff);
        $this->assertStringContainsString('Mở / Chia sẻ tệp', $handoff);
        $this->assertStringContainsString('event.stopImmediatePropagation();', $handoff);
        $this->assertStringContainsString('panel.shareButton.disabled = false;', $handoff);
        $this->assertStringNotContainsString('}, {once: true});', $handoff);
        $this->assertStringNotContainsString("window.open(link.href", $handoff);
        $this->assertStringNotContainsString('window.location.assign(link.href)', $handoff);
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
        $this->assertStringContainsString('navigator.share({ files: [...] })', $contract);
        $this->assertStringContainsString('Hidden iframe to the binary URL', $contract);
        $this->assertStringContainsString("window.open(binaryUrl, '_blank')", $contract);
        $this->assertStringContainsString('iOS installed PWA', $contract);
        $this->assertStringContainsString('Android installed PWA', $contract);
        $this->assertStringContainsString('Desktop / normal browser', $contract);
    }
}
