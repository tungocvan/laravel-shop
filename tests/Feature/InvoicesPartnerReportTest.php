<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\InvoicePartnerReportService;
use Tests\TestCase;

class InvoicesPartnerReportTest extends TestCase
{
    public function test_partner_report_aggregates_sold_purchase_vat_and_net(): void
    {
        Schema::dropIfExists('invoice_files');
        Schema::dropIfExists('invoices');
        (require base_path('Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php'))->up();

        try {
            Invoices::query()->create($this->row('1', 'sold', '1000.00', '100.00'));
            Invoices::query()->create($this->row('2', 'purchase', '400.00', '40.00'));

            $service = app(InvoicePartnerReportService::class);
            $filters = ['issued_date_from'=>'2026-08-01','issued_date_to'=>'2026-08-31'];
            $summary = $service->summary($filters);
            $partners = $service->exportRows($filters);

            $this->assertSame(2, $summary['invoice_count']);
            $this->assertSame('1000', (string) $summary['sold_total']);
            $this->assertSame('400', (string) $summary['purchase_total']);
            $this->assertCount(1, $partners);
            $this->assertSame(2, (int) $partners->first()->invoice_count);
            $this->assertSame('600', (string) $partners->first()->net_total);
        } finally {
            Schema::dropIfExists('invoice_files');
            Schema::dropIfExists('invoices');
        }
    }

    private function row(string $number, string $type, string $total, string $vat): array
    {
        return ['lookup_code'=>null,'symbol'=>'1/C26TAA','invoice_number'=>$number,'type'=>'Hóa đơn GTGT','issued_date'=>'2026-08-10','tax_code'=>'0312345678','name'=>'Công ty A','tax_rate'=>'10','vat_amount'=>$vat,'amount_before_vat'=>'900.00','total_amount'=>$total,'invoice_type'=>$type];
    }
}
