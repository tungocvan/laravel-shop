<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaOrderPdfActionsContractTest extends TestCase
{
    #[Test]
    public function posted_order_actions_offer_distinct_download_and_print_on_mobile_and_desktop(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php'));

        $this->assertSame(2, substr_count($blade, "route('client.pharma.orders.pdf',\$issue)"));
        $this->assertSame(2, substr_count($blade, "route('client.pharma.orders.print',\$issue)"));
        $this->assertSame(2, substr_count($blade, '>Tải PDF</a>'));
        $this->assertSame(2, substr_count($blade, '>In PDF</a>'));
        $this->assertStringContainsString("data-order-pdf-download", $blade);
        $this->assertStringContainsString("orderPdfActions[\$issue->id]['ready']", $blade);
    }

    #[Test]
    public function pdf_routes_reuse_authorized_pharma_document_service(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));

        $this->assertStringContainsString("'orders.pdf'", $routes);
        $this->assertStringContainsString("'orders.print'", $routes);
        $this->assertStringContainsString('visibleOrderForDocument($issue,$request,$registry,$workspace)', $controller);
        $this->assertStringContainsString('InventoryIssueDocumentService $documents', $controller);
        $this->assertStringContainsString("['Cache-Control'=>'private, no-store']", $controller);
    }
}
