<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaOrderPdfActionsContractTest extends TestCase
{
    public function test_client_pdf_export_supports_direct_download_and_print_without_bypassing_document_validation(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php'));

        $this->assertStringContainsString("'pdf_action' => ['nullable', 'in:download,print']", $controller);
        $this->assertStringContainsString('$documents->generate($visible,(int)$user->id);', $controller);
        $this->assertStringContainsString('redirect()->route(\'client.pharma.orders.pdf\', $visible)', $controller);
        $this->assertStringContainsString('redirect()->route(\'client.pharma.orders.print\', $visible)', $controller);
        $this->assertStringContainsString('$documents->current($visible)', $controller);
        $this->assertSame(2, substr_count($view, 'name="pdf_action" value="download"'));
        $this->assertSame(2, substr_count($view, 'name="pdf_action" value="print"'));
        $this->assertStringContainsString('Xuất và tải PDF', $view);
        $this->assertStringContainsString('Xuất và in PDF', $view);
    }
}
