<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Applications\Muasamcong\Models\PriceListExport as MuasamcongPriceListExport;
use Modules\ClientPortal\Applications\Muasamcong\Models\PublicShare as MuasamcongPublicShare;
use Modules\ClientPortal\Applications\Muasamcong\Models\SyncRequest as MuasamcongSyncRequest;
use Modules\ClientPortal\Models\PriceListExport as LegacyPriceListExport;
use Modules\ClientPortal\Models\PublicShare as LegacyPublicShare;
use Modules\ClientPortal\Models\SyncRequest as LegacySyncRequest;
use Tests\TestCase;

class ClientPortalArchitectureContractTest extends TestCase
{
    public function test_client_portal_has_only_auth_as_direct_module_dependency(): void
    {
        $manifest = require base_path('Modules/ClientPortal/config/module.php');

        $this->assertSame('support', $manifest['type']);
        $this->assertSame(['Auth'], $manifest['depends']);
        $this->assertNotContains('Muasamcong', $manifest['depends']);
        $this->assertNotContains('Request', $manifest['depends']);
    }

    public function test_muasamcong_client_state_has_adapter_scoped_canonical_models(): void
    {
        $this->assertSame('client_portal_price_list_exports', (new MuasamcongPriceListExport)->getTable());
        $this->assertSame('client_portal_public_shares', (new MuasamcongPublicShare)->getTable());
        $this->assertSame('client_portal_sync_requests', (new MuasamcongSyncRequest)->getTable());
    }

    public function test_legacy_root_model_names_are_compatibility_aliases(): void
    {
        $this->assertInstanceOf(MuasamcongPriceListExport::class, new LegacyPriceListExport);
        $this->assertInstanceOf(MuasamcongPublicShare::class, new LegacyPublicShare);
        $this->assertInstanceOf(MuasamcongSyncRequest::class, new LegacySyncRequest);
    }

    public function test_muasamcong_adapter_runtime_uses_canonical_model_namespace(): void
    {
        $paths = [
            'Modules/ClientPortal/Applications/Muasamcong/Http/Controllers/MuasamcongApplicationController.php',
            'Modules/ClientPortal/Applications/Muasamcong/Http/Controllers/MuasamcongPriceListController.php',
            'Modules/ClientPortal/Applications/Muasamcong/Http/Controllers/MuasamcongShareManagementController.php',
            'Modules/ClientPortal/Applications/Muasamcong/Http/Controllers/PublicDrugShareController.php',
            'Modules/ClientPortal/Applications/Muasamcong/Jobs/SyncPricingResultsJob.php',
        ];

        foreach ($paths as $path) {
            $source = file_get_contents(base_path($path));

            $this->assertIsString($source, $path);
            $this->assertStringNotContainsString('Modules\\ClientPortal\\Models\\', $source, $path);
        }
    }

    public function test_module_architecture_contract_exists(): void
    {
        $this->assertFileExists(base_path('docs/modules/ClientPortal/MODULE.md'));
    }
}
