<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Applications\Invoices\Http\Controllers\InvoicesApplicationController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesPosPwaFilterRetentionContractTest extends TestCase
{
    #[Test]
    public function pos_filter_snapshot_survives_captcha_error_redirect(): void
    {
        $controller = file_get_contents((new \ReflectionClass(InvoicesApplicationController::class))->getFileName());
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/sync.blade.php'));
        $this->assertStringContainsString("'client.invoices.pos.last_filters'", $controller);
        $this->assertStringContainsString("'posLastFilters' =>", $controller);
        $this->assertStringContainsString("session()->put('client.invoices.pos.last_filters'", $controller);
        $this->assertStringContainsString('$posLastFilters ??', $view);
        $this->assertStringContainsString('$posLastFilters ||', $view);
        $this->assertStringContainsString('Bộ lọc đã được giữ lại', $controller);
    }
}
