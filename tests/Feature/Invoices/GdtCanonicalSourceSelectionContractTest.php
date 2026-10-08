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
        $this->assertStringContainsString("$"."source !== 'pos' ?", $service);
        $this->assertStringContainsString("$"."source !== 'regular' ?", $service);
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
        $this->assertStringContainsString('value="regular"', $view);
        $this->assertStringContainsString('value="pos"', $view);
        $this->assertStringContainsString('KHÔNG cần Import lại', $view);
    }

    #[Test]
    public function queued_pos_sync_bypasses_all_source_shortcuts(): void
    {
        $job = file_get_contents((new \ReflectionClass(ProcessGdtInvoicesJob::class))->getFileName());
        $this->assertStringContainsString("if ($"."this->source !== 'all')", $job);
        $this->assertStringContainsString("$"."this->vatIn, $"."this->source)", $job);
        $this->assertStringContainsString("public string $"."source = 'all'", $job);
    }
}
