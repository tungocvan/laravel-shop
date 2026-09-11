<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Integrations\Inventory\InvoiceLineNormalizer;
use Modules\Invoices\Support\GdtInvoiceLineMetadata;
use Tests\TestCase;

final class StructuredLotExpiryMappingTest extends TestCase
{
    public function test_structured_gdt_metadata_is_preserved_for_camzitol(): void
    {
        $line = $this->camzitolLine();

        $normalized = app(InvoiceLineNormalizer::class)->normalize($line);

        $this->assertSame('G0846', GdtInvoiceLineMetadata::lotNumber($line));
        $this->assertSame('2028-03-08', GdtInvoiceLineMetadata::expiryDate($line));
        $this->assertSame('G0846', $normalized['lot_number']);
        $this->assertSame('2028-03-08', $normalized['expiry_date']);
    }

    public function test_text_fallback_remains_supported_when_structured_metadata_is_missing(): void
    {
        $normalized = app(InvoiceLineNormalizer::class)->normalize([
            'ten' => 'Cefmetazol 2g (Hộp 10 lọ); Lô: C60D001; HSD: 07/06/2027; NSX: Việt Nam',
            'dvtinh' => 'Lọ',
        ]);

        $this->assertSame('C60D001', $normalized['lot_number']);
        $this->assertSame('2027-06-07', $normalized['expiry_date']);
    }

    public function test_structured_metadata_wins_over_conflicting_text_fallback(): void
    {
        $line = $this->camzitolLine();
        $line['ten'] = 'CAMZITOL; Lô: WRONG123; HSD: 01/01/2030';

        $normalized = app(InvoiceLineNormalizer::class)->normalize($line);

        $this->assertSame('G0846', $normalized['lot_number']);
        $this->assertSame('2028-03-08', $normalized['expiry_date']);
    }

    public function test_missing_lot_and_expiry_remain_null(): void
    {
        $normalized = app(InvoiceLineNormalizer::class)->normalize([
            'ten' => 'Dịch vụ vận chuyển',
            'dvtinh' => 'Lần',
        ]);

        $this->assertNull($normalized['lot_number']);
        $this->assertNull($normalized['expiry_date']);
    }

    public function test_inventory_contract_uses_normalizer_so_text_fallback_reaches_inventory(): void
    {
        $factory = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php'));

        $this->assertStringContainsString('$normalizedLine = $this->lineNormalizer->normalize($line);', $factory);
        $this->assertStringContainsString("'lot_number' => \$normalizedLine['lot_number'] ?? null", $factory);
        $this->assertStringContainsString("'expiry_date' => \$normalizedLine['expiry_date'] ?? null", $factory);
        $this->assertStringContainsString("'manufacture_date' => \$normalizedLine['manufacture_date'] ?? null", $factory);
        $this->assertStringContainsString("'normalizer_version' => InvoiceLineNormalizer::VERSION", $factory);
    }

    public function test_inventory_contract_generated_pdf_and_source_data_use_shared_structured_metadata_extractor(): void
    {
        $factory = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php'));
        $pdf = file_get_contents(base_path('Modules/Invoices/resources/views/pdf/gdt-invoice.blade.php'));
        $manager = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $sourceDataView = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager.blade.php'));

        $this->assertStringContainsString('$this->lineNormalizer->normalize($line)', $factory);
        $this->assertStringContainsString('GdtInvoiceLineMetadata::lotNumber($item)', $pdf);
        $this->assertStringContainsString('GdtInvoiceLineMetadata::expiryDate($item)', $pdf);
        $this->assertStringContainsString("'lot_number' => GdtInvoiceLineMetadata::lotNumber(\$item)", $manager);
        $this->assertStringContainsString("'expiry_date' => GdtInvoiceLineMetadata::expiryDate(\$item)", $manager);
        $this->assertStringContainsString("\$item['lot_number']", $sourceDataView);
        $this->assertStringContainsString("\$item['expiry_date']", $sourceDataView);
        $this->assertStringContainsString('Số lô', $pdf);
        $this->assertStringContainsString('Hạn sử dụng', $pdf);
        $this->assertStringContainsString('Số lô', $sourceDataView);
        $this->assertStringContainsString('Hạn sử dụng', $sourceDataView);
    }

    private function camzitolLine(): array
    {
        return [
            'stt' => 1,
            'ten' => 'CAMZITOL; NSX: Farmalabor Produtos Farmacêuticos, S.A (Fab.) - Bồ Đào Nha',
            'dvtinh' => 'Viên',
            'sluong' => 39000,
            'dgia' => 2240,
            'thtien' => 87360000,
            'ttkhac' => [
                ['dlieu' => '2028-03-08', 'kdlieu' => 'date', 'ttruong' => 'ExpiryDate'],
                ['dlieu' => 'G0846', 'kdlieu' => 'string', 'ttruong' => 'LotNo'],
            ],
        ];
    }
}
