<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class HsspDossierEngineContractTest extends TestCase
{
    public function test_hssp_uses_reusable_dossier_engine_and_required_validity_fields(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 4).'/Modules/Pharma/Http/Controllers/HsspController.php');
        $view = file_get_contents(dirname(__DIR__, 4).'/Modules/Pharma/resources/views/pages/hssp/form.blade.php');
        $template = file_get_contents(dirname(__DIR__, 4).'/Modules/Pharma/Services/HsspDossierTemplateService.php');
        $component = file_get_contents(dirname(__DIR__, 4).'/resources/views/components/dossier/editor.blade.php');
        $storage = file_get_contents(dirname(__DIR__, 4).'/app/Dossiers/Services/DossierStorageService.php');
        $queueJob = file_get_contents(dirname(__DIR__, 4).'/Modules/Pharma/Jobs/UploadHsspAttachmentToGoogleDrive.php');

        $this->assertStringContainsString('DossierManager $dossiers', $controller);
        $this->assertStringContainsString("'items.gmp.effective_to' => ['required', 'date']", $controller);
        $this->assertStringContainsString("'items.registration.effective_to' => ['required', 'date']", $controller);
        $this->assertStringContainsString('enctype="multipart/form-data"', $view);
        $this->assertStringContainsString('<x-dossier.editor', $view);
        $this->assertStringContainsString('+ Thêm mục hồ sơ', $component);
        $this->assertStringContainsString('name="master_files[]"', $component);
        $this->assertStringContainsString("'pharma-product-dossier'", $template);
        $this->assertStringContainsString("'allows_multiple_files' => true", $template);
        $this->assertStringContainsString("'pending'", $storage);
        $this->assertStringContainsString("->onQueue('pharma')", $storage);
        $this->assertStringContainsString("self::TARGET_GOOGLE_DRIVE", $storage);
        $this->assertStringContainsString("'Pharma/HSSP/'", $controller);
        $this->assertStringContainsString("'storage_targets' => ['required', 'array', 'min:1']", $controller);
        $this->assertStringContainsString('Laravel-Backup/Pharma/HSSP/', $view);
        $this->assertStringContainsString('upload_max_filesize', $view);
        $this->assertStringContainsString('post_max_size', $view);
        $this->assertStringContainsString('safe_post_bytes', $storage);
        $this->assertStringContainsString('ini_get(\'upload_max_filesize\')', $storage);
        $this->assertStringContainsString("->onQueue('pharma')", $storage);
        $this->assertStringContainsString("implements ShouldQueue", $queueJob);
        $this->assertStringContainsString("onQueue('pharma')", $queueJob);
        $this->assertStringContainsString("'Pharma/HSSP/'.\$this->hsspFolderName(\$medicine)", $controller);
        $this->assertStringContainsString('Xác nhận lưu hồ sơ sản phẩm', $view);
        $this->assertStringContainsString('File giữ nguyên tên gốc', $view);
    }

    public function test_generic_dossier_schema_is_not_owned_by_pharma(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 4).'/database/migrations/2026_09_19_083000_create_dossier_engine_tables.php');

        foreach (['dossier_templates', 'dossier_template_items', 'dossiers', 'dossier_items', 'dossier_attachments'] as $table) {
            $this->assertStringContainsString("Schema::create('{$table}'", $migration);
        }

        $this->assertStringContainsString("\$table->string('owner_type')", $migration);
        $this->assertStringContainsString("\$table->json('metadata_schema')->nullable()", $migration);
    }
}
