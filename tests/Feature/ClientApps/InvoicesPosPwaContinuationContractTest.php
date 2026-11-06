<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Applications\Invoices\Http\Controllers\InvoicesApplicationController;
use Modules\ClientPortal\Applications\Invoices\Services\ClientInvoiceWorkspaceService;
use Modules\Invoices\Services\GdtInvoiceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesPosPwaContinuationContractTest extends TestCase
{
    #[Test]
    public function client_pos_uses_shared_encrypted_page_two_continuation_and_verified_canonical(): void
    {
        $controller = file_get_contents((new \ReflectionClass(InvoicesApplicationController::class))->getFileName());
        $workspace = file_get_contents((new \ReflectionClass(ClientInvoiceWorkspaceService::class))->getFileName());
        $service = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/sync.blade.php'));

        $this->assertStringContainsString('GdtPosSecureContinuation::class', $controller);
        $this->assertStringContainsString("'preview_page2', 'sync_page2'", $controller);
        $this->assertSame(2, substr_count($controller, "\$snapshot['rows']"));
        $this->assertStringNotContainsString("\$snapshot['first_page']", $controller);
        $this->assertStringContainsString('->resumePosPreview(', $controller);
        $this->assertStringContainsString('->resumePosCanonicalRows(', $controller);
        $this->assertStringContainsString('->takePosCompleteFirstPageRows()', $controller);
        $this->assertStringContainsString('->syncVerifiedPos(', $controller);
        $this->assertStringContainsString('->processVerifiedPosRows(', $workspace);
        $this->assertStringContainsString('public function takePosCompleteFirstPageRows()', $service);
        $this->assertStringContainsString('Tải CAPTCHA trang 2', $view);
        $this->assertStringContainsString('name="force_refresh"', $view);
        $this->assertStringContainsString('private function existingPosFile(', $controller);
        $this->assertStringContainsString('POS chỉ hỗ trợ một tháng dương lịch mỗi lần.', $controller);
    }
}
