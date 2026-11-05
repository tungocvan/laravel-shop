<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Livewire\SearchHoadon;
use Modules\Invoices\Services\GdtInvoiceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtSyncDashboardContractTest extends TestCase
{
    #[Test]
    public function dashboard_displays_separate_source_metrics_without_inventing_counts(): void
    {
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/search-hoadon.blade.php'));
        $component = file_get_contents((new \ReflectionClass(SearchHoadon::class))->getFileName());

        $this->assertStringContainsString('role="tablist"', $view);
        $this->assertStringContainsString("searchPreview['count']", $view);
        $this->assertStringContainsString('Chưa tìm kiếm', $view);
        $this->assertStringContainsString('sourceResults', $component);
        $this->assertStringContainsString('lastSourceStats()', $component);
        $this->assertStringContainsString('updatedInvoiceSource', $component);
        $this->assertStringContainsString('$this->useQueue = false;', $component);
    }

    #[Test]
    public function service_tracks_source_counts_and_queue_publishes_them(): void
    {
        $service = file_get_contents((new \ReflectionClass(GdtInvoiceService::class))->getFileName());
        $job = file_get_contents((new \ReflectionClass(ProcessGdtInvoicesJob::class))->getFileName());

        $this->assertStringContainsString('public function lastSourceStats(): array', $service);
        $this->assertStringContainsString("['state' => \$source === 'pos' ? 'skipped'", $service);
        $this->assertStringContainsString("['state' => \$source === 'regular' ? 'skipped'", $service);
        $this->assertStringContainsString("count(\$regular)", $service);
        $this->assertStringContainsString("count(\$pos)", $service);
        $this->assertStringContainsString("'source_results' => \$service->lastSourceStats()", $job);
    }
}
