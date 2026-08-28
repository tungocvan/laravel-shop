<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Services\ApplicationRegistry;
use Tests\TestCase;

class ClientPortalMuasamcongReferenceMigrationTest extends TestCase
{
    public function test_muasamcong_manifest_registers_route_scoped_shell_extensions(): void
    {
        $application = app(ApplicationRegistry::class)->find('muasamcong');

        $this->assertNotNull($application);

        $head = $application['shell_extensions']['head'] ?? [];
        $overlays = $application['shell_extensions']['overlays'] ?? [];

        $this->assertSame(
            ['client.muasamcong.price-list*'],
            $head[0]['routes'] ?? null
        );
        $this->assertSame(
            'ClientPortal::applications.muasamcong.partials.price-list-workspace-polish',
            $head[0]['view'] ?? null
        );
        $this->assertSame(
            ['client.muasamcong.drug-pricing*'],
            $overlays[0]['routes'] ?? null
        );
        $this->assertSame(
            'ClientPortal::applications.muasamcong.partials.sync-queue-status',
            $overlays[0]['view'] ?? null
        );
    }

    public function test_shared_application_shell_has_no_muasamcong_specific_route_or_queue_logic(): void
    {
        $layout = file_get_contents(base_path('Modules/ClientPortal/resources/views/layouts/application.blade.php'));

        $this->assertStringContainsString("shell_extensions", $layout);
        $this->assertStringContainsString("request()->routeIs(...\$extension['routes'])", $layout);
        $this->assertStringNotContainsString('client.muasamcong', $layout);
        $this->assertStringNotContainsString('sync_request_id', $layout);
        $this->assertStringNotContainsString('price-list-workspace-polish', $layout);
        $this->assertStringNotContainsString('queue-status', $layout);
    }

    public function test_muasamcong_application_layer_owns_price_list_and_sync_status_presentation(): void
    {
        $priceListPolish = file_get_contents(base_path(
            'Modules/ClientPortal/resources/views/applications/muasamcong/partials/price-list-workspace-polish.blade.php'
        ));
        $syncStatus = file_get_contents(base_path(
            'Modules/ClientPortal/resources/views/applications/muasamcong/partials/sync-queue-status.blade.php'
        ));

        $this->assertStringContainsString('.export-card', $priceListPolish);
        $this->assertStringContainsString('sync_request_id', $syncStatus);
        $this->assertStringContainsString('client.muasamcong.drug-pricing.sync-status', $syncStatus);
        $this->assertStringContainsString('id="queue-status"', $syncStatus);
        $this->assertStringContainsString("cache: 'no-store'", $syncStatus);
    }

    public function test_shell_extension_normalization_drops_invalid_rules(): void
    {
        $registrySource = file_get_contents(base_path('Modules/ClientPortal/Services/ApplicationRegistry.php'));

        $this->assertStringContainsString('normalizeShellExtensions', $registrySource);
        $this->assertStringContainsString("['head', 'overlays', 'scripts']", $registrySource);
        $this->assertStringContainsString("\$rule['view']", $registrySource);
        $this->assertStringContainsString("\$rule['routes']", $registrySource);
    }
}
