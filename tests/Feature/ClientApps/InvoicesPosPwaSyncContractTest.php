<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Invoices\Http\Controllers\InvoicesApplicationController;
use Modules\ClientPortal\Applications\Invoices\Services\ClientInvoiceWorkspaceService;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesPosPwaSyncContractTest extends TestCase
{
    #[Test]
    public function pos_endpoints_are_web_authenticated_and_feature_protected(): void
    {
        foreach (['client.invoices.sync.pos.captcha', 'client.invoices.sync.pos.submit'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('client.feature:invoices,sync', $route->gatherMiddleware());
            $this->assertContains('throttle:10,1', $route->gatherMiddleware());
            $this->assertContains('POST', $route->methods());
        }
    }

    #[Test]
    public function pos_uses_canonical_service_with_no_queue_or_browser_credentials(): void
    {
        $controller = file_get_contents((new \ReflectionClass(InvoicesApplicationController::class))->getFileName());
        $workspace = file_get_contents((new \ReflectionClass(ClientInvoiceWorkspaceService::class))->getFileName());
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/sync.blade.php'));

        $this->assertStringContainsString("previewSource(\$start, \$end, \$direction === 'purchase', 'pos', \$challenge)", $workspace);
        $this->assertStringContainsString("processRange(\$start, \$end, null, \$direction === 'purchase', 'pos', null, \$challenge)", $workspace);
        $this->assertStringContainsString("ProcessGdtInvoicesJob::dispatch(\$start, \$end, \$direction === 'purchase', \$syncId, 'regular')", $workspace);
        $this->assertStringContainsString("client.invoices.pos.preview", $controller);
        $this->assertStringContainsString("client.invoices.pos.filters", $controller);
        $this->assertStringContainsString("getAuthIdentifier().'-'.\$request->session()->getId()", $controller);
        $this->assertStringContainsString('client.invoices.sync.pos.captcha', $view);
        $this->assertStringContainsString('client.invoices.sync.pos.submit', $view);
        $this->assertStringContainsString('<x-pwa-date', $view);
        $this->assertStringNotContainsString('name="password"', $view);
    }

    #[Test]
    public function pos_queue_remains_fail_closed_and_manifest_supports_managed_copy(): void
    {
        $job = file_get_contents((new \ReflectionClass(ProcessGdtInvoicesJob::class))->getFileName());
        $manifest = require base_path('Modules/ClientPortal/Applications/Invoices/manifest.php');

        $this->assertStringContainsString("if (\$this->source === 'pos')", $job);
        $this->assertSame('Đồng bộ hóa đơn', $manifest['features']['sync']['page_title']);
        $this->assertArrayHasKey('eyebrow', $manifest['features']['sync']);
        $this->assertArrayHasKey('page_description', $manifest['features']['sync']);
    }
}
