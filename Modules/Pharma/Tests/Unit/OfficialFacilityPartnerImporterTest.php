<?php

namespace Modules\Pharma\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;
use Modules\Pharma\Models\OfficialFacilityImportBatch;
use Modules\Pharma\Models\OfficialFacilityImportRow;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialFacilityPartnerImporter;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class OfficialFacilityPartnerImporterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function unselected_row_cannot_write_partner(): void
    {
        $row = $this->row(['is_selected' => false, 'classification' => 'NEW']);

        $this->expectException(RuntimeException::class);
        app(OfficialFacilityPartnerImporter::class)->import($row);
    }

    #[Test]
    public function selected_new_row_creates_hospital_customer_and_reference(): void
    {
        $row = $this->row(['is_selected' => true, 'classification' => 'NEW']);

        $partner = app(OfficialFacilityPartnerImporter::class)->import($row);

        $this->assertSame('hospital', $partner->legal_type);
        $this->assertSame(['customer'], $partner->partner_types);
        $this->assertSame('92', $partner->province_code);
        $this->assertSame('import', $partner->source);
        $this->assertDatabaseHas('partner_source_references', [
            'partner_id' => $partner->id,
            'source' => 'bhxh',
            'external_id' => 'BV-001',
        ]);
    }

    #[Test]
    public function reimporting_same_source_identity_does_not_duplicate_partner(): void
    {
        $first = $this->row(['is_selected' => true, 'classification' => 'NEW']);
        $partner = app(OfficialFacilityPartnerImporter::class)->import($first);

        $second = $this->row([
            'is_selected' => true,
            'classification' => 'EXACT',
            'matched_partner_id' => $partner->id,
            'row_number' => 3,
        ]);

        app(OfficialFacilityPartnerImporter::class)->import($second);

        $this->assertSame(1, Partner::query()->where('name', 'Bệnh viện Test')->count());
        $this->assertSame(1, PartnerSourceReference::query()->where('source', 'bhxh')->where('external_id', 'BV-001')->count());
    }

    #[Test]
    public function existing_manual_contact_fields_are_not_overwritten(): void
    {
        $partner = Partner::query()->create([
            'name' => 'Bệnh viện Test',
            'legal_type' => 'hospital',
            'partner_types' => ['customer'],
            'phone' => '0900000000',
            'email' => 'manual@example.test',
            'contact_person' => 'Manual Owner',
            'source' => 'manual',
            'status' => 'active',
        ]);

        $row = $this->row([
            'is_selected' => true,
            'classification' => 'EXACT',
            'matched_partner_id' => $partner->id,
            'phone' => '0911111111',
            'email' => 'official@example.test',
        ]);

        app(OfficialFacilityPartnerImporter::class)->import($row);
        $partner->refresh();

        $this->assertSame('0900000000', $partner->phone);
        $this->assertSame('manual@example.test', $partner->email);
        $this->assertSame('Manual Owner', $partner->contact_person);
        $this->assertSame('Bệnh viện Test', $partner->name);
    }

    private function row(array $overrides = []): OfficialFacilityImportRow
    {
        $batch = OfficialFacilityImportBatch::query()->firstOrCreate(
            ['sha256' => str_repeat('a', 64)],
            [
                'source' => 'bhxh',
                'province_code' => '92',
                'source_province_code' => '92TTT',
                'original_filename' => 'test.xlsx',
                'stored_path' => 'test.xlsx',
                'status' => 'READY',
            ]
        );

        return OfficialFacilityImportRow::query()->create(array_merge([
            'batch_id' => $batch->id,
            'row_number' => 2,
            'external_id' => 'BV-001',
            'facility_name' => 'Bệnh viện Test',
            'normalized_name' => 'benh vien test',
            'address' => 'Cần Thơ',
            'normalized_address' => 'can tho',
            'province_code' => '92',
            'source_province_code' => '92TTT',
            'phone' => '0911111111',
            'email' => 'official@example.test',
            'classification' => 'NEW',
            'is_selected' => true,
        ], $overrides));
    }
}
