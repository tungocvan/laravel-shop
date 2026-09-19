<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class HsspDossierEngineContractTest extends TestCase
{
    public function test_hssp_uses_reusable_dossier_engine_and_required_validity_fields(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/HsspController.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/hssp/form.blade.php'));
        $template = file_get_contents(base_path('Modules/Pharma/Services/HsspDossierTemplateService.php'));
        $component = file_get_contents(base_path('resources/views/components/dossier/editor.blade.php'));
        $storage = file_get_contents(base_path('app/Dossiers/Services/DossierStorageService.php'));

        $this->assertStringContainsString('DossierManager $dossiers', $controller);
        $this->assertStringContainsString("'items.gmp.effective_to' => ['required', 'date']", $controller);
        $this->assertStringContainsString("'items.registration.effective_to' => ['required', 'date']", $controller);
        $this->assertStringContainsString('enctype="multipart/form-data"', $view);
        $this->assertStringContainsString('<x-dossier.editor', $view);
        $this->assertStringContainsString('+ Thêm mục hồ sơ', $component);
        $this->assertStringContainsString('name="master_files[]"', $component);
        $this->assertStringContainsString("'pharma-product-dossier'", $template);
        $this->assertStringContainsString("'allows_multiple_files' => true", $template);
        $this->assertStringContainsString("'Laravel-Backup/'", $storage);
        $this->assertStringContainsString("'sync_failed'", $storage);
    }

    public function test_generic_dossier_schema_is_not_owned_by_pharma(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_09_19_083000_create_dossier_engine_tables.php'));

        foreach (['dossier_templates', 'dossier_template_items', 'dossiers', 'dossier_items', 'dossier_attachments'] as $table) {
            $this->assertStringContainsString("Schema::create('{$table}'", $migration);
        }

        $this->assertStringContainsString("$table->string('owner_type')", $migration);
        $this->assertStringContainsString("$table->json('metadata_schema')->nullable()", $migration);
    }
}
