<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Services\InvoiceImportExportService;
use Modules\Invoices\Services\InvoiceService;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class InvoicesModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoices_module_boots(): void
    {
        $this->assertTrue(class_exists(\Modules\Invoices\Providers\InvoicesServiceProvider::class));
    }

    public function test_invoice_dashboard_route_is_registered(): void
    {
        $this->assertTrue(app('router')->has('admin.invoices.dashboard'));
    }

    public function test_invoice_admin_routes_are_registered(): void
    {
        foreach ([
            'admin.invoices.index',
            'admin.invoices.create-token',
            'admin.invoices.dashboard',
            'admin.invoices.download-invoice',
            'admin.invoices.download',
            'admin.invoices.hoadon',
            'admin.invoices.hoadon-list',
            'admin.invoices.reports.partners',
        ] as $routeName) {
            $this->assertTrue(app('router')->has($routeName), "Missing route: {$routeName}");
        }
    }

    public function test_invoice_dashboard_aggregates_expected_totals(): void
    {
        $this->withInvoicesTable(function () {
            DB::table('invoices')->insert([
                $this->invoiceRow('dash-sold-2026-a', 'sold', '10', '100.00', '10.00', '2026-08-15', 'Customer A'),
                $this->invoiceRow('dash-sold-2026-b', 'sold', '10', '200.00', '20.00', '2026-07-15', 'Customer A'),
                $this->invoiceRow('dash-purchase-2026', 'purchase', '8', '300.00', '24.00', '2026-06-15', 'Vendor A'),
                $this->invoiceRow('dash-sold-2025', 'sold', '5', '400.00', '20.00', '2025-06-15', 'Customer B'),
            ]);

            DB::flushQueryLog();
            DB::enableQueryLog();

            $dashboard = app(InvoiceService::class)->dashboard();

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $this->assertCount(2, $queries);
            $this->assertEquals(700.0, (float) $dashboard['sold_amount']);
            $this->assertEquals(300.0, (float) $dashboard['purchase_amount']);
            $this->assertSame(2, $dashboard['sold_customers']);
            $this->assertSame(1, $dashboard['purchase_customers']);
            $this->assertSame(2026, (int) $dashboard['yearly'][0]['year']);
            $this->assertEquals(300.0, (float) $dashboard['yearly'][0]['sold_total']);
            $this->assertEquals(300.0, (float) $dashboard['yearly'][0]['purchase_total']);
            $this->assertSame(2025, (int) $dashboard['yearly'][1]['year']);
            $this->assertEquals(400.0, (float) $dashboard['yearly'][1]['sold_total']);
        });
    }

    public function test_invoice_export_honors_filters_and_selected_ids(): void
    {
        $this->withInvoicesTable(function () {
            $firstId = DB::table('invoices')->insertGetId($this->invoiceRow('export-a', 'sold', '10'));
            $secondId = DB::table('invoices')->insertGetId($this->invoiceRow('export-b', 'purchase', '8'));

            $service = app(InvoiceImportExportService::class);
            $filteredPath = $service->export(['invoice_type' => 'sold']);
            $selectedPath = $service->export(['invoice_type' => 'sold', 'selected_ids' => [$secondId, $secondId, -1, 'bad']]);
            $filteredAbsolutePath = $service->exportAbsolutePath($filteredPath);
            $selectedAbsolutePath = $service->exportAbsolutePath($selectedPath);

            try {
                $filtered = (new FastExcel)->import($filteredAbsolutePath);
                $selected = (new FastExcel)->import($selectedAbsolutePath);

                $this->assertCount(1, $filtered);
                $this->assertSame('export-a', (string) $filtered->first()['Mã tra cứu']);
                $this->assertCount(1, $selected);
                $this->assertSame('export-b', (string) $selected->first()['Mã tra cứu']);
                $this->assertNotSame($firstId, $secondId);
            } finally {
                @unlink($filteredAbsolutePath);
                @unlink($selectedAbsolutePath);
            }
        });
    }

    private function withInvoicesTable(callable $callback): void
    {
        Schema::dropIfExists('invoices');
        $migration = require base_path('Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php');
        $migration->up();

        try {
            $callback();
        } finally {
            DB::disableQueryLog();
            Schema::dropIfExists('invoices');
        }
    }

    private function invoiceRow(
        string $lookupCode,
        string $type,
        string $taxRate,
        string $totalAmount = '1100.00',
        string $vatAmount = '100.00',
        string $issuedDate = '2026-08-15',
        ?string $name = null
    ): array {
        return [
            'lookup_code' => $lookupCode,
            'symbol' => '1C26TAA',
            'invoice_number' => '000001',
            'type' => 'Hóa đơn GTGT',
            'issued_date' => $issuedDate,
            'tax_code' => '0100000000',
            'name' => $name ?? 'Đối tác',
            'address' => 'Hà Nội',
            'email' => null,
            'phone' => null,
            'tax_rate' => $taxRate,
            'vat_amount' => $vatAmount,
            'amount_before_vat' => bcsub($totalAmount, $vatAmount, 2),
            'total_amount' => $totalAmount,
            'invoice_type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
