<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\Invoices\Services\InvoiceImportService;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;
use Modules\Partner\Models\PartnerSyncCandidate;
use Modules\Partner\Services\PartnerCandidateIntakeService;
use Modules\Partner\Services\PartnerCandidateReviewService;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class InvoicesPartnerCandidateSyncTest extends TestCase
{
    protected function tearDown(): void
    {
        Schema::dropIfExists('partner_sync_candidates');
        Schema::dropIfExists('partner_source_references');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('invoices');

        parent::tearDown();
    }

    public function test_invoice_import_stages_customer_candidate_without_mutating_partner_master(): void
    {
        $this->migrateFixtures();

        $file = storage_path('app/invoices-partner-candidate-test.xlsx');
        (new FastExcel([[
            'Mã tra cứu' => 'candidate-lookup',
            'Ký hiệu' => '1/C26T',
            'Số hóa đơn' => 'PC-001',
            'Loại hóa đơn' => 'Hóa đơn GTGT',
            'Ngày lập' => '08/09/2026',
            'Mã số thuế' => '0312345678',
            'Đơn vị' => 'Công ty Khách Hàng',
            'Địa chỉ' => '1 Nguyễn Huệ',
            'Email' => 'customer@example.com',
            'Phone' => '0909000000',
            'Thuế suất' => '10',
            'Tiền VAT' => '100',
            'Trước VAT' => '1000',
            'Thành tiền' => '1100',
        ]]))->export($file);

        try {
            $count = app(InvoiceImportService::class)->import($file, 'sold');

            $this->assertSame(1, $count);
            $this->assertDatabaseHas('invoices', [
                'lookup_code' => 'candidate-lookup',
                'invoice_type' => 'sold',
                'tax_code' => '0312345678',
            ]);

            $candidate = PartnerSyncCandidate::query()->where('tax_code', '0312345678')->firstOrFail();
            $this->assertSame('invoices', $candidate->source);
            $this->assertSame('pending', $candidate->status);
            $this->assertSame(['customer'], $candidate->partner_types);
            $this->assertSame('Công ty Khách Hàng', $candidate->name);
            $this->assertNull($candidate->matched_partner_id);
            $this->assertSame(0, Partner::query()->count());
        } finally {
            @unlink($file);
        }
    }

    public function test_candidate_intake_marks_conflict_without_overwriting_existing_partner(): void
    {
        $this->migrateFixtures(false);

        $partner = Partner::query()->create([
            'tax_code' => '0300000001',
            'name' => 'Tên ERP Hiện Tại',
            'address' => 'Địa chỉ ERP',
            'email' => 'erp@example.com',
            'phone' => '0901000000',
            'partner_types' => ['customer'],
            'source' => 'manual',
            'status' => 'active',
        ]);

        $summary = app(PartnerCandidateIntakeService::class)->intake('invoices', [[
            'tax_code' => '0300000001',
            'name' => 'Tên Trên Hóa Đơn',
            'address' => 'Địa chỉ ERP',
            'email' => 'erp@example.com',
            'phone' => '0901000000',
            'partner_types' => ['supplier'],
        ]]);

        $this->assertSame(1, $summary['conflict']);

        $candidate = PartnerSyncCandidate::query()->where('tax_code', '0300000001')->firstOrFail();
        $this->assertSame('conflict', $candidate->status);
        $this->assertSame($partner->getKey(), $candidate->matched_partner_id);
        $this->assertSame('different', $candidate->conflict_fields['name']['state']);

        $partner->refresh();
        $this->assertSame('Tên ERP Hiện Tại', $partner->name);
        $this->assertSame(['customer'], $partner->partner_types);
    }

    public function test_review_can_create_partner_and_promote_invoice_provenance(): void
    {
        $this->migrateFixtures(false);

        $candidate = PartnerSyncCandidate::query()->create([
            'source' => 'invoices',
            'tax_code' => '0300000002',
            'name' => 'Partner Mới',
            'address' => '2 Lê Lợi',
            'email' => 'new@example.com',
            'phone' => '0902000000',
            'partner_types' => ['supplier'],
            'status' => 'pending',
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
        ]);

        $partner = app(PartnerCandidateReviewService::class)->createPartner($candidate);

        $this->assertSame('0300000002', $partner->tax_code);
        $this->assertSame(['supplier'], $partner->partner_types);
        $this->assertSame('system', $partner->source);

        $candidate->refresh();
        $this->assertSame('matched', $candidate->status);
        $this->assertSame($partner->getKey(), $candidate->matched_partner_id);

        $reference = PartnerSourceReference::query()
            ->where('source', 'invoices')
            ->where('external_id', '0300000002')
            ->firstOrFail();
        $this->assertSame($partner->getKey(), $reference->partner_id);
    }

    public function test_review_existing_partner_updates_only_selected_fields_and_merges_roles(): void
    {
        $this->migrateFixtures(false);

        $partner = Partner::query()->create([
            'tax_code' => '0300000003',
            'name' => 'Tên ERP',
            'address' => 'Địa chỉ ERP',
            'email' => 'erp@example.com',
            'phone' => '0903000000',
            'partner_types' => ['customer'],
            'source' => 'manual',
            'status' => 'active',
        ]);

        $candidate = PartnerSyncCandidate::query()->create([
            'source' => 'invoices',
            'tax_code' => '0300000003',
            'name' => 'Tên Hóa Đơn',
            'address' => 'Địa chỉ Hóa Đơn',
            'email' => 'invoice@example.com',
            'phone' => '0999999999',
            'partner_types' => ['supplier'],
            'status' => 'conflict',
            'matched_partner_id' => $partner->getKey(),
            'conflict_fields' => [
                'name' => ['state' => 'different'],
                'address' => ['state' => 'different'],
            ],
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
        ]);

        app(PartnerCandidateReviewService::class)->confirmExisting($candidate, ['address', 'partner_types']);

        $partner->refresh();
        $this->assertSame('Tên ERP', $partner->name);
        $this->assertSame('Địa chỉ Hóa Đơn', $partner->address);
        $this->assertSame('erp@example.com', $partner->email);
        $this->assertSame(['customer', 'supplier'], $partner->partner_types);

        $candidate->refresh();
        $this->assertSame('matched', $candidate->status);
        $this->assertNull($candidate->conflict_fields);
        $this->assertSame(['address', 'partner_types'], $candidate->source_metadata['applied_fields']);
    }

    private function migrateFixtures(bool $withInvoices = true): void
    {
        Schema::dropIfExists('partner_sync_candidates');
        Schema::dropIfExists('partner_source_references');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('invoices');

        (require base_path('Modules/Partner/database/migrations/2026_05_26_095912_partners.php'))->up();
        (require base_path('Modules/Partner/database/migrations/2026_09_05_201000_create_partner_source_references_table.php'))->up();
        (require base_path('Modules/Partner/database/migrations/2026_09_08_100000_create_partner_sync_candidates_table.php'))->up();

        if ($withInvoices) {
            (require base_path('Modules/Invoices/database/migrations/2025_11_21_045614_invoices.php'))->up();
        }
    }
}
