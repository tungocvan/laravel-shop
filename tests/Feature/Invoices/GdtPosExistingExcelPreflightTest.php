<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\File;
use Modules\Invoices\Services\GdtCompletedExport;
use Tests\TestCase;

class GdtPosExistingExcelPreflightTest extends TestCase
{
    public function test_existing_pos_excel_is_detected_without_completion_manifest(): void
    {
        $directory = 'gdt-pos-existing-test-'.bin2hex(random_bytes(8));
        config()->set('invoices.storage.export_directory', $directory);
        $file = app(GdtCompletedExport::class)->path('2026-09-01', '2026-09-30', true, 'pos');
        File::ensureDirectoryExists(dirname($file));
        try {
            file_put_contents($file, 'existing-file');
            $this->assertFileExists($file);
            $this->assertNull(app(GdtCompletedExport::class)->completed('2026-09-01', '2026-09-30', true, 'pos'));
        } finally {
            File::deleteDirectory(storage_path('app/'.$directory));
        }
    }

    public function test_pos_captcha_is_guarded_and_existing_import_action_is_reused(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SearchHoadon.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));
        $this->assertStringContainsString("if (\$purpose === 'search' && \$this->existingPosExcel() !== null && ! \$this->forceResync)", $component);
        $this->assertStringContainsString('public function selectExistingPosExcel()', $component);
        $this->assertStringContainsString('public function importSelectedFile()', $component);
        $this->assertStringContainsString('wire:click="selectExistingPosExcel"', $view);
        $this->assertStringContainsString('wire:click="importSelectedFile"', $view);
        $this->assertStringContainsString('x-on:pos-existing-file-selected.window', $view);
    }
}
