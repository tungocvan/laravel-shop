<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Services\InvoiceWorkspaceService;
use Tests\TestCase;

class InvoicesWorkspaceServiceTest extends TestCase
{
    public function test_export_without_selection_returns_all_records_in_filtered_scope(): void
    {
        $this->withInvoicesTable(function () {
            DB::table('invoices')->insert([
                $this->invoiceRow('sold-a', 'sold', 'S-001', '2026-08-01'),
                $this->invoiceRow('sold-b', 'sold', 'S-002', '2026-08-02'),
                $this->invoiceRow('purchase-a', 'purchase', 'P-001', '2026-08-03'),
            ]);

            $records = app(InvoiceWorkspaceService::class)->exportRecords([
                'invoice_type' => 'sold',
                'issued_date_from' => '2026-08-01',
                'issued_date_to' => '2026-08-31',
            ], []);

            $this->assertSame(['S-002', 'S-001'], $records->pluck('invoice_number')->all());
        });
    }

    public function test_export_with_selection_ignores_broader_filter_and_returns_selected_ids_only(): void
    {
        $this->withInvoicesTable(function () {
            DB::table('invoices')->insert([
                $this->invoiceRow('sold-a', 'sold', 'S-001', '2026-08-01'),
                $this->invoiceRow('sold-b', 'sold', 'S-002', '2026-08-02'),
                $this->invoiceRow('purchase-a', 'purchase', 'P-001', '2026-08-03'),
            ]);

            $selectedId = (int) DB::table('invoices')->where('invoice_number', 'P-001')->value('id');
            $records = app(InvoiceWorkspaceService::class)->exportRecords(['invoice_type' => 'sold'], [$selectedId]);

            $this->assertSame(['P-001'], $records->pluck('invoice_number')->all());
        });
    }

    public function test_all_filtered_ids_returns_complete_scope_not_one_page(): void
    {
        $this->withInvoicesTable(function () {
            for ($i = 1; $i <= 15; $i++) {
                DB::table('invoices')->insert($this->invoiceRow(
                    'sold-'.$i,
                    'sold',
                    'S-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    '2026-08-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                ));
            }

            $service = app(InvoiceWorkspaceService::class);

            $this->assertCount(10, $service->pageIds(['invoice_type' => 'sold'], 10));
            $this->assertCount(15, $service->allFilteredIds(['invoice_type' => 'sold']));
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
            Schema::dropIfExists('invoices');
        }
    }

    private function invoiceRow(string $lookupCode, string $invoiceType, string $invoiceNumber, string $issuedDate): array
    {
        return [
            'lookup_code' => $lookupCode,
            'symbol' => '1/C26T',
            'invoice_number' => $invoiceNumber,
            'type' => 'Hóa đơn GTGT',
            'issued_date' => $issuedDate,
            'tax_code' => 'tax-'.$invoiceNumber,
            'name' => 'Partner '.$invoiceNumber,
            'address' => '',
            'email' => '',
            'phone' => '',
            'tax_rate' => '10',
            'vat_amount' => '10.00',
            'amount_before_vat' => '90.00',
            'total_amount' => '100.00',
            'invoice_type' => $invoiceType,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
