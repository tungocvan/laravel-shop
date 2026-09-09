<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Integrations\Inventory\InvoiceLineNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceInventoryBulkIntakeContractTest extends TestCase
{
    #[Test]
    public function staging_schema_supports_pending_error_retry_and_raw_audit(): void
    {
        $migration = file_get_contents(base_path('Modules/Invoices/database/migrations/2026_09_09_170000_create_invoice_inventory_staging_tables.php'));

        $this->assertStringContainsString("->char('payload_hash', 64)->nullable()", $migration);
        $this->assertStringContainsString("->string('status', 32)->default('PENDING')", $migration);
        $this->assertStringContainsString("->json('raw_payload')->nullable()", $migration);
        $this->assertStringContainsString("->unsignedInteger('attempt_count')->default(0)", $migration);
        $this->assertStringContainsString("->timestamp('last_attempt_at')->nullable()", $migration);
        $this->assertStringContainsString("->text('raw_description')", $migration);
        $this->assertStringContainsString("->string('tax_rate', 32)->nullable()", $migration);
        $this->assertStringContainsString("->string('lot_number')->nullable()", $migration);
        $this->assertStringContainsString("->date('expiry_date')->nullable()", $migration);
    }

    #[Test]
    public function staging_service_is_resumable_idempotent_and_persists_failures(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceInventoryStagingService.php'));

        $this->assertStringContainsString("['status' => 'PENDING']", $service);
        $this->assertStringContainsString("'status' => 'FETCHING'", $service);
        $this->assertStringContainsString("'attempt_count' => ((int) \$snapshot->attempt_count) + 1", $service);
        $this->assertStringContainsString("hash('sha256'", $service);
        $this->assertStringContainsString('$snapshot->payload_hash === $hash', $service);
        $this->assertStringContainsString("'tax_rate' => isset(\$line['tsuat'])", $service);
        $this->assertStringContainsString("'status' => 'NORMALIZED'", $service);
        $this->assertStringContainsString("'status' => 'ERROR'", $service);
        $this->assertStringContainsString("'last_error' => mb_substr", $service);
        $this->assertStringContainsString("'raw_payload' => \$detail", $service);
        $this->assertStringContainsString("'raw_payload' => \$line", $service);
        $this->assertStringContainsString("\$rawDescription = (string) (\$line['ten'] ?? '')", $service);
        $this->assertStringContainsString("'raw_description' => \$rawDescription", $service);
    }

    #[Test]
    public function bulk_dispatch_is_bounded_by_date_chunk_and_queue(): void
    {
        $bulk = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/BulkInvoiceInventoryIntakeService.php'));

        $this->assertStringContainsString("->where('invoice_type', 'purchase')", $bulk);
        $this->assertStringContainsString("->whereBetween('issued_date'", $bulk);
        $this->assertStringContainsString('min($batchSize, 500)', $bulk);
        $this->assertStringContainsString('->chunkById($batchSize', $bulk);
        $this->assertStringContainsString('StageInvoiceForInventory::dispatch', $bulk);
        $this->assertStringContainsString("->onQueue('default')", $bulk);
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
