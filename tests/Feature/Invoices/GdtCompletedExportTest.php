<?php

namespace Tests\Feature\Invoices;

use Illuminate\Support\Facades\File;
use Modules\Invoices\Services\GdtCompletedExport;
use Tests\TestCase;

class GdtCompletedExportTest extends TestCase
{
    public function test_existing_excel_without_completion_manifest_is_not_skipped(): void
    {
        $directory = 'gdt-completion-test-'.bin2hex(random_bytes(8));
        config()->set('invoices.storage.export_directory', $directory);
        $service = app(GdtCompletedExport::class);
        $file = $service->path('2026-09-01', '2026-09-30', true, 'pos');
        File::ensureDirectoryExists(dirname($file));
        try {
            file_put_contents($file, 'fake-excel');
            $this->assertNull($service->completed('2026-09-01', '2026-09-30', true, 'pos'));
            $service->mark('2026-09-01', '2026-09-30', true, 'pos', $file);
            $this->assertSame($file, $service->completed('2026-09-01', '2026-09-30', true, 'pos'));
            file_put_contents($file, 'tampered');
            $this->assertNull($service->completed('2026-09-01', '2026-09-30', true, 'pos'));
            $this->assertNull($service->completed('2026-09-01', '2026-09-30', true, 'regular'));
        } finally {
            File::deleteDirectory(storage_path('app/'.$directory));
        }
    }

    public function test_preflight_occurs_before_pos_captcha_and_existing_export_can_be_explicitly_refreshed(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SearchHoadon.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));
        $this->assertStringContainsString("if (\$purpose === 'sync' && \$this->skipCompletedCanonical())", $component);
        $this->assertStringContainsString('confirmCanonicalResync', $component);
        $this->assertStringContainsString('wire:confirm=', $view);
        $this->assertStringContainsString('Đồng bộ lại', $view);
    }
}
