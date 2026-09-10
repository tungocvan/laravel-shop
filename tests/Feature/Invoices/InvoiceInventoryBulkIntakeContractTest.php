<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Integrations\Inventory\InvoiceLineNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceInventoryBulkIntakeContractTest extends TestCase
{
    #[Test]
    public function canonical_source_schema_owns_gdt_raw_and_business_annotations(): void
    {
        $migration = file_get_contents(base_path('Modules/Invoices/database/migrations/2026_09_10_020000_create_invoice_source_records_table.php'));

        $this->assertStringContainsString("Schema::create('invoice_source_records'", $migration);
        $this->assertStringContainsString("->json('header_payload')->nullable()", $migration);
        $this->assertStringContainsString("->json('detail_payload')->nullable()", $migration);
        $this->assertStringContainsString("->char('header_hash', 64)->nullable()", $migration);
        $this->assertStringContainsString("->char('detail_hash', 64)->nullable()", $migration);
        $this->assertStringContainsString("->string('detail_status', 32)->default('MISSING')", $migration);
        $this->assertStringContainsString("->string('business_classification', 32)->default('UNCLASSIFIED')", $migration);
        $this->assertStringContainsString("->string('classification_scope', 16)->default('INVOICE')", $migration);
        $this->assertStringContainsString('backfillLegacyInventoryRaw', $migration);
    }

    #[Test]
    public function inventory_staging_is_projection_and_never_acquires_gdt(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceInventoryStagingService.php'));

        $this->assertStringContainsString("['status' => 'PENDING']", $service);
        $this->assertStringContainsString('$this->gdtDetailService->storedDetail($invoice)', $service);
        $this->assertStringContainsString('RAW GDT detail canonical', $service);
        $this->assertStringNotContainsString('fetchAndStoreDetail(', $service);
        $this->assertStringNotContainsString("'raw_payload' => \$detail", $service);
        $this->assertStringContainsString("'raw_payload' => \$line", $service);
        $this->assertStringContainsString("'status' => 'NORMALIZED'", $service);
        $this->assertStringContainsString("'status' => 'ERROR'", $service);
        $this->assertStringContainsString('updateOrCreate(', $service);
    }

    #[Test]
    public function gdt_detail_service_is_local_only_for_consumers_and_explicit_for_acquisition(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtPdfService.php'));

        $this->assertStringContainsString('public function storedDetail(Invoices $invoice): ?array', $service);
        $this->assertStringContainsString('public function fetchDetail(Invoices $invoice, bool $force = false): array', $service);
        $this->assertStringContainsString('public function fetchAndStoreDetail(Invoices $invoice): array', $service);
        $this->assertStringContainsString('Chưa có RAW GDT detail trên server.', $service);
        $this->assertStringContainsString("'detail_status' => 'READY'", $service);
        $this->assertStringContainsString("'detail_status' => 'ERROR'", $service);
    }

    #[Test]
    public function hoadon_sync_persists_full_header_and_missing_detail_raw_once(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));
        $job = file_get_contents(base_path('Modules/Invoices/Jobs/ProcessGdtInvoicesJob.php'));

        $this->assertStringContainsString("'_gdt_raw_payload' => \$item", $service);
        $this->assertStringContainsString('persistRawHeader(', $service);
        $this->assertStringContainsString('acquireMissingDetails(', $service);
        $this->assertStringContainsString('$service->storedDetail($invoice) !== null', $service);
        $this->assertStringContainsString('$service->fetchAndStoreDetail($invoice)', $service);
        $this->assertStringContainsString('InvoiceSourceCoverageService $coverage', $job);
        $this->assertStringContainsString('File Excel đã tồn tại nhưng RAW canonical chưa đầy đủ', $job);
        $this->assertStringContainsString('&& $canonicalReady', $job);
    }

    #[Test]
    public function gdt_sync_preflights_token_before_bulk_queue(): void
    {
        $api = file_get_contents(base_path('Modules/Invoices/Services/GdtApiService.php'));
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SearchHoadon.php'));

        $this->assertStringContainsString('public function assertTokenUsable(): void', $api);
        $this->assertStringContainsString('in_array($response->status(), [401, 403], true)', $api);
        $this->assertStringContainsString('$this->forgetToken()', $api);
        $this->assertStringContainsString('if (! $this->apiService->hasToken())', $component);
        $this->assertStringContainsString("redirectRoute('admin.invoices.create-token')", $component);
    }

    #[Test]
    public function source_data_workspace_manages_annotations_without_direct_gdt_calls(): void
    {
        $routes = file_get_contents(base_path('Modules/Invoices/routes/web.php'));
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager.blade.php'));

        $this->assertStringContainsString("'/source-data'", $routes);
        $this->assertStringContainsString("name('source-data')", $routes);
        $this->assertStringContainsString('saveAnnotation', $component);
        $this->assertStringContainsString("'classification_scope' => \$applySupplierWide ? 'SUPPLIER' : 'INVOICE'", $component);
        $this->assertStringContainsString('applySameTaxCode', $component);
        $this->assertStringContainsString('Hóa đơn mới cùng MST sẽ kế thừa quy tắc này.', $component);
        $this->assertStringContainsString('Áp dụng cùng phân loại cho tất cả hóa đơn cùng MST', $view);
        $this->assertStringNotContainsString('GdtApiService', $component);
        $this->assertStringNotContainsString('GdtInvoiceService', $component);
        $this->assertStringNotContainsString('GdtPdfService', $component);
    }

    #[Test]
    public function supplier_rules_are_inherited_by_future_source_records(): void
    {
        $model = file_get_contents(base_path('Modules/Invoices/Models/InvoiceSourceRecord.php'));

        $this->assertStringContainsString("->where('classification_scope', 'SUPPLIER')", $model);
        $this->assertStringContainsString("->where('business_classification', '!=', 'UNCLASSIFIED')", $model);
        $this->assertStringContainsString("->where('tax_code', \$taxCode)", $model);
        $this->assertStringContainsString('$record->business_classification = $supplierRule->business_classification', $model);
    }

    #[Test]
    public function bulk_dispatch_only_queues_purchase_invoices_with_persisted_ready_raw(): void
    {
        $bulk = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/BulkInvoiceInventoryIntakeService.php'));

        $this->assertStringContainsString("->where('invoice_type', 'purchase')", $bulk);
        $this->assertStringContainsString("->whereBetween('issued_date'", $bulk);
        $this->assertStringContainsString("->whereHas('sourceRecord'", $bulk);
        $this->assertStringContainsString("->where('detail_status', 'READY')", $bulk);
        $this->assertStringContainsString("->whereNotNull('detail_payload')", $bulk);
        $this->assertStringContainsString('min($batchSize, 500)', $bulk);
        $this->assertStringContainsString('StageInvoiceForInventory::dispatch', $bulk);
        $this->assertStringContainsString("->onQueue('default')", $bulk);
    }

    #[Test]
    public function staging_tracks_normalizer_version_and_supports_local_raw_renormalization(): void
    {
        $migration = file_get_contents(base_path('Modules/Invoices/database/migrations/2026_09_09_200200_add_normalizer_version_to_invoice_inventory_snapshots_table.php'));
        $service = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceInventoryStagingService.php'));
        $normalizer = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceLineNormalizer.php'));

        $this->assertStringContainsString("->string('normalizer_version', 64)", $migration);
        $this->assertStringContainsString("public const VERSION = 'deterministic-v3'", $normalizer);
        $this->assertStringContainsString('$snapshot->normalizer_version === $normalizerVersion', $service);
        $this->assertStringContainsString('$this->gdtDetailService->storedDetail($invoice)', $service);
        $this->assertStringContainsString("'normalizer_version' => \$normalizerVersion", $service);
        $this->assertStringContainsString('updateOrCreate(', $service);
    }

    #[Test]
    public function deterministic_normalizer_preserves_product_identity_and_separates_lot_and_expiry(): void
    {
        $normalized = app(InvoiceLineNormalizer::class)->normalize([
            'ten' => 'PARACETAMOL 500MG HỘP 10 VỈ X 10 VIÊN Số lô: P240801 - HSD: 08/2028',
            'dvtinh' => 'Hộp',
        ]);

        $this->assertSame('PARACETAMOL 500MG HỘP 10 VỈ X 10 VIÊN', $normalized['normalized_name']);
        $this->assertSame('500MG', $normalized['strength']);
        $this->assertSame('HỘP 10 VỈ X 10 VIÊN', $normalized['package_spec']);
        $this->assertSame('P240801', $normalized['lot_number']);
        $this->assertSame('2028-08-01', $normalized['expiry_date']);
        $this->assertSame('hộp', $normalized['normalized_uom']);
        $this->assertSame('deterministic-v3', $normalized['normalization_meta']['parser']);
    }

    #[Test]
    public function deterministic_normalizer_handles_real_pharma_semicolon_format(): void
    {
        $normalized = app(InvoiceLineNormalizer::class)->normalize([
            'ten' => 'Cefmetazol 2g (Hộp 10 lọ); Lô: C60D001; HSD: 07/06/2027; NSX: Việt Nam',
            'dvtinh' => 'Lọ',
        ]);

        $this->assertSame('Cefmetazol 2g (Hộp 10 lọ)', $normalized['normalized_name']);
        $this->assertSame('2g', $normalized['strength']);
        $this->assertSame('Hộp 10 lọ', $normalized['package_spec']);
        $this->assertSame('C60D001', $normalized['lot_number']);
        $this->assertSame('2027-06-07', $normalized['expiry_date']);
        $this->assertSame('Việt Nam', $normalized['manufacturer']);
        $this->assertNull($normalized['manufacture_date']);
        $this->assertNull($normalized['dosage_form']);
        $this->assertSame('lọ', $normalized['normalized_uom']);
        $this->assertSame('deterministic-v3', $normalized['normalization_meta']['parser']);
    }

    #[Test]
    public function deterministic_v3_handles_real_khang_phat_invoice_261_lot_and_hd_alias(): void
    {
        $normalizer = app(InvoiceLineNormalizer::class);

        $first = $normalizer->normalize([
            'ten' => 'Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU), Lô: 26001CN, HD: 28/03/2029',
            'dvtinh' => 'Lọ',
        ]);
        $second = $normalizer->normalize([
            'ten' => 'Cefuroxime 125mg/5ml ( H/1C.TT/60,8g BPHDU), Lô: 26002CN, HD: 30/03/2029',
            'dvtinh' => 'Lọ',
        ]);

        $this->assertSame('26001CN', $first['lot_number']);
        $this->assertSame('2029-03-28', $first['expiry_date']);
        $this->assertStringNotContainsString('26001CN', $first['normalized_name']);
        $this->assertStringNotContainsString('HD:', $first['normalized_name']);
        $this->assertStringContainsString('Cefuroxime 125mg/5ml', $first['normalized_name']);

        $this->assertSame('26002CN', $second['lot_number']);
        $this->assertSame('2029-03-30', $second['expiry_date']);
        $this->assertStringNotContainsString('26002CN', $second['normalized_name']);
        $this->assertStringNotContainsString('HD:', $second['normalized_name']);
        $this->assertStringContainsString('Cefuroxime 125mg/5ml', $second['normalized_name']);
        $this->assertSame('deterministic-v3', $second['normalization_meta']['parser']);
    }
}
