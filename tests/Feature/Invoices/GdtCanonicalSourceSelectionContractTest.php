<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Livewire\SearchHoadon;
use Modules\Invoices\Services\GdtInvoiceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtCanonicalSourceSelectionContractTest extends TestCase
{
    #[Test]
    public function all_three_sources_use_canonical_persistence(): void
    {
        $service = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());
        $this->assertStringContainsString("['all', 'regular', 'pos']", $service);
        $this->assertStringContainsString("if (\$source !== 'pos')", $service);
        $this->assertStringContainsString("isset(\$checkpoint['regular'])", $service);
        $this->assertStringContainsString("persistInvoices(\$regular, \$vatIn)", $service);
        $this->assertStringContainsString("if (\$source === 'regular')", $service);
        $this->assertStringContainsString("isset(\$checkpoint['pos'])", $service);
        $this->assertStringContainsString("fetchInvoicesByMonth(\$token, \$chunkStart, \$chunkEnd, \$show, \$vatIn, true, \$posCaptcha)", $service);
        $this->assertStringContainsString('persistInvoices($all, $vatIn)', $service);
        $this->assertStringContainsString('acquireMissingDetails($stats[', $service);
    }

    #[Test]
    public function pos_ui_no_longer_uses_preview_only_import(): void
    {
        $component = file_get_contents((new \ReflectionClass(SearchHoadon::class))->getFileName());
        $view = file_get_contents(resource_path('../Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));
        $this->assertStringContainsString("'in:all,regular,pos'", $component);
        $this->assertStringNotContainsString('exportCashRegisterPreview(', $component);
        $this->assertStringContainsString('role="tablist"', $view);
        $this->assertStringContainsString('wire:click="$set(\'invoiceSource\', \'regular\')"', $view);
        $this->assertStringContainsString('wire:click="$set(\'invoiceSource\', \'pos\')"', $view);
        $this->assertStringContainsString("'openPosCaptcha' : 'searchInvoices'", $view);
        $this->assertStringContainsString("'openPosSyncCaptcha' : 'run'", $view);
        $this->assertStringContainsString('wire:click="submitPosCaptcha"', $view);
        $this->assertStringContainsString('public function submitPosCaptcha(): void', $component);
        $this->assertStringContainsString('KHÔNG cần Import lại', $view);
    }

    #[Test]
    public function queued_pos_sync_fails_closed_before_any_gdt_service_call(): void
    {
        $service = $this->createMock(GdtInvoiceService::class);
        $service->expects($this->never())->method('processRange');

        $job = new ProcessGdtInvoicesJob('2026-10-01', '2026-10-30', true, null, 'pos');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('chỉ được đồng bộ trực tiếp, không qua queue');

        $job->handle(
            $service,
            $this->createMock(\Modules\Invoices\Services\GoogleDriveInvoiceExportService::class),
            $this->createMock(\Modules\Invoices\Services\InvoiceSourceCoverageService::class),
        );
    }

    #[Test]
    public function source_scoped_job_uses_canonical_path_without_all_source_shortcuts(): void
    {
        $job = file_get_contents((new \ReflectionClass(ProcessGdtInvoicesJob::class))->getFileName());
        $this->assertStringContainsString("if ($"."this->source !== 'all')", $job);
        $this->assertStringContainsString("$"."this->vatIn, $"."this->source)", $job);
        $this->assertStringContainsString("public string $"."source = 'all'", $job);
    }
}
