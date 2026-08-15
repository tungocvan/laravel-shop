<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Services\InvoiceService;
use Tests\TestCase;

class InvoicesFilterSortTest extends TestCase
{
    public function test_purchase_filter_keeps_invoices_without_lookup_code_visible(): void
    {
        $this->withInvoicesTable(function () {
            DB::table('invoices')->insert([
                $this->invoiceRow(null, 'purchase', 'PUR-001', '2026-08-10', 'Vendor Without Lookup', '500.00'),
                $this->invoiceRow('sold-lookup', 'sold', 'SOL-001', '2026-08-10', 'Sold Customer', '600.00'),
            ]);

            $result = app(InvoiceService::class)->filter([
                'invoice_type' => 'purchase',
                'issued_date_from' => '2026-08-01',
                'issued_date_to' => '2026-08-31',
            ]);

            $this->assertCount(1, $result);
            $this->assertNull($result->first()->lookup_code);
            $this->assertSame('Vendor Without Lookup', $result->first()->name);
        });
    }

    public function test_amount_sort_is_whitelisted_and_orders_both_directions(): void
    {
        $this->withInvoicesTable(function () {
            DB::table('invoices')->insert([
                $this->invoiceRow('a', 'purchase', '1', '2026-08-10', 'A', '100.00'),
                $this->invoiceRow('b', 'purchase', '2', '2026-08-11', 'B', '900.00'),
                $this->invoiceRow('c', 'purchase', '3', '2026-08-12', 'C', '500.00'),
            ]);

            $service = app(InvoiceService::class);

            $desc = $service->filter(['invoice_type' => 'purchase', 'sort' => 'amount_desc']);
            $asc = $service->filter(['invoice_type' => 'purchase', 'sort' => 'amount_asc']);
            $invalid = $service->filter(['invoice_type' => 'purchase', 'sort' => 'total_amount desc; drop table invoices']);

            $this->assertSame(['2', '3', '1'], $desc->pluck('invoice_number')->all());
            $this->assertSame(['1', '3', '2'], $asc->pluck('invoice_number')->all());
            $this->assertSame(['3', '2', '1'], $invalid->pluck('invoice_number')->all());
            $this->assertTrue(Schema::hasTable('invoices'));
        });
    }

    public function test_year_options_are_distinct_and_newest_first(): void
    {
        $this->withInvoicesTable(function () {
            DB::table('invoices')->insert([
                $this->invoiceRow('2025-a', 'sold', '1', '2025-03-01', 'A', '100.00'),
                $this->invoiceRow('2026-a', 'purchase', '2', '2026-01-01', 'B', '200.00'),
                $this->invoiceRow('2026-b', 'purchase', '3', '2026-08-01', 'C', '300.00'),
            ]);

            $this->assertSame([2026, 2025], app(InvoiceService::class)->years());
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

    private function invoiceRow(
        ?string $lookupCode,
        string $invoiceType,
        string $invoiceNumber,
        string $issuedDate,
        string $name,
        string $totalAmount
    ): array {
        return [
            'lookup_code' => $lookupCode,
            'symbol' => '1/C26T',
            'invoice_number' => $invoiceNumber,
            'type' => 'Hóa đơn GTGT',
            'issued_date' => $issuedDate,
            'tax_code' => 'tax-'.$invoiceNumber,
            'name' => $name,
            'address' => '',
            'email' => '',
            'phone' => '',
            'tax_rate' => '10',
            'vat_amount' => '10.00',
            'amount_before_vat' => '90.00',
            'total_amount' => $totalAmount,
            'invoice_type' => $invoiceType,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
