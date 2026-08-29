<?php

namespace Tests\Feature\Pharma;

use Modules\Pharma\Services\ImportExport;
use Tests\TestCase;

class PharmaSecurityImportExportFoundationTest extends TestCase
{
    public function test_pharma_routes_declare_capability_boundaries_and_public_api_is_closed(): void
    {
        $webRoutes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $apiRoutes = file_get_contents(base_path('Modules/Pharma/routes/api.php'));

        $this->assertStringContainsString("middleware('can:view_pharma')", $webRoutes);
        $this->assertStringContainsString("middleware('can:create_pharma')", $webRoutes);
        $this->assertStringContainsString("middleware('can:edit_pharma')", $webRoutes);
        $this->assertStringNotContainsString("Route::get('/', 'index')", $apiRoutes);
    }

    public function test_livewire_write_boundaries_use_pharma_authorization(): void
    {
        $files = [
            'Modules/Pharma/Livewire/Medicine/Index.php' => 'authorizePharmaDelete',
            'Modules/Pharma/Livewire/Medicine/Form.php' => 'authorizePharmaCreate',
            'Modules/Pharma/Livewire/DrugBidAward/Index.php' => 'authorizePharmaDelete',
            'Modules/Pharma/Livewire/DrugBidAward/Form.php' => 'authorizePharmaCreate',
            'Modules/Pharma/Livewire/SupplierTrackings/Index.php' => 'authorizePharmaDelete',
            'Modules/Pharma/Livewire/SupplierTrackings/Form.php' => 'authorizePharmaCreate',
            'Modules/Pharma/Livewire/PriceList/Create.php' => 'authorizePharmaCreate',
        ];

        foreach ($files as $path => $authorizationCall) {
            $this->assertStringContainsString(
                $authorizationCall,
                file_get_contents(base_path($path)),
                "Expected {$path} to enforce {$authorizationCall}."
            );
        }
    }

    public function test_supplier_import_export_is_permission_bound_and_replace_is_disabled(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/supplier-trackings/index.blade.php'));
        $service = app(ImportExport::class);

        $this->assertStringContainsString("'permission' => 'edit_pharma'", $view);
        $this->assertSame(
            ['create_only', 'update_or_create', 'skip_duplicate'],
            $service->allowedImportModes()
        );
        $this->assertNotContains('replace', $service->allowedImportModes());
    }

    public function test_shared_panel_locks_server_owned_state_and_uses_private_exports(): void
    {
        $panel = file_get_contents(base_path('Modules/Shared/Livewire/ImportExport/Panel.php'));
        $baseService = file_get_contents(base_path('Modules/Shared/Services/ImportExport/BaseImportExportService.php'));
        $storageConcern = file_get_contents(base_path('Modules/Shared/Services/ImportExport/Concerns/HandlesExportStorage.php'));

        $this->assertStringContainsString('#[Locked]', $panel);
        $this->assertStringContainsString('allowedImportModes()', $panel);
        $this->assertStringContainsString('deleteFileAfterSend(true)', $panel);
        $this->assertStringContainsString('$this->exportAbsolutePath($path)', $baseService);
        $this->assertStringContainsString("return 'local';", $storageConcern);
        $this->assertStringNotContainsString("Storage::disk('public')", $storageConcern);
    }
}
