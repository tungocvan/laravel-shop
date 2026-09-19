<?php

namespace Tests\Feature\Invoices;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\InvoicePartnerCandidateService;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSyncCandidate;
use Tests\TestCase;

class InvoicePartnerSyncContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_partners_are_aggregated_by_tax_code_and_roles_are_merged(): void
    {
        Invoices::query()->create([
            'lookup_code' => 'sold-1',
            'tax_code' => '0301234567',
            'name' => 'Công ty ABC',
            'address' => 'TP.HCM',
            'invoice_type' => 'sold',
            'issued_date' => '2026-09-01',
        ]);
        Invoices::query()->create([
            'lookup_code' => 'purchase-1',
            'tax_code' => '0301234567',
            'name' => 'Công ty ABC',
            'phone' => '0909000000',
            'invoice_type' => 'purchase',
            'issued_date' => '2026-09-10',
        ]);

        $result = app(InvoicePartnerCandidateService::class)->sync();

        $this->assertSame(1, $result['total']);
        $candidate = PartnerSyncCandidate::query()->where('source', 'invoices')->where('tax_code', '0301234567')->firstOrFail();
        $this->assertEqualsCanonicalizing(['customer', 'supplier'], $candidate->partner_types);
        $this->assertSame(1, $candidate->source_metadata['sold_invoice_count']);
        $this->assertSame(1, $candidate->source_metadata['purchase_invoice_count']);
        $this->assertSame('0909000000', $candidate->phone);
    }

    public function test_existing_partner_is_matched_without_mutating_master_data(): void
    {
        $partner = Partner::query()->create([
            'tax_code' => '0307654321',
            'name' => 'Tên Master',
            'legal_type' => 'company',
            'partner_types' => ['supplier'],
            'source' => 'manual',
            'status' => 'active',
        ]);

        Invoices::query()->create([
            'lookup_code' => 'purchase-2',
            'tax_code' => '0307654321',
            'name' => 'Tên Master',
            'invoice_type' => 'purchase',
            'issued_date' => '2026-09-12',
        ]);

        app(InvoicePartnerCandidateService::class)->sync();

        $candidate = PartnerSyncCandidate::query()->where('tax_code', '0307654321')->firstOrFail();
        $this->assertSame('matched', $candidate->status);
        $this->assertSame($partner->id, $candidate->matched_partner_id);
        $this->assertSame('Tên Master', $partner->fresh()->name);
    }

    public function test_invoice_without_tax_code_is_not_sent_to_partner_intake(): void
    {
        Invoices::query()->create([
            'lookup_code' => 'missing-tax',
            'name' => 'Khách lẻ',
            'invoice_type' => 'sold',
            'issued_date' => '2026-09-15',
        ]);

        app(InvoicePartnerCandidateService::class)->sync();

        $this->assertDatabaseCount('partner_sync_candidates', 0);
        $this->assertSame(1, app(InvoicePartnerCandidateService::class)->summary()['missing_identity']);
    }
}
