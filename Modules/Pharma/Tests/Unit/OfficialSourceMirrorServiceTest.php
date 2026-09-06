<?php

namespace Modules\Pharma\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\OfficialSourceSyncBatch;
use Modules\Pharma\Services\OfficialFacilityImport\OfficialSourceMirrorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficialSourceMirrorServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function province_snapshot_creates_and_then_marks_unchanged_records(): void
    {
        $service = app(OfficialSourceMirrorService::class);
        $first = $this->batch();

        $service->persist($first, $this->facilities());
        $first->refresh();

        $this->assertSame('COMPLETED', $first->status);
        $this->assertSame(2, $first->created_count);
        $this->assertSame(0, $first->updated_count);
        $this->assertDatabaseHas('pharma_official_source_facilities', [
            'source' => 'bhxh',
            'external_id' => '93108',
            'source_province_code' => '92TTT',
            'is_active' => true,
        ]);

        $second = $this->batch();
        $service->persist($second, $this->facilities());
        $second->refresh();

        $this->assertSame(0, $second->created_count);
        $this->assertSame(0, $second->updated_count);
        $this->assertSame(2, $second->unchanged_count);
    }

    #[Test]
    public function full_province_snapshot_marks_missing_previous_records_stale_without_deleting_them(): void
    {
        $service = app(OfficialSourceMirrorService::class);
        $service->persist($this->batch(), $this->facilities());

        $next = $this->batch();
        $service->persist($next, [$this->facilities()[0]]);
        $next->refresh();

        $this->assertSame(1, $next->stale_count);
        $this->assertDatabaseHas('pharma_official_source_facilities', [
            'external_id' => '94170',
            'is_active' => false,
        ]);
        $this->assertSame(2, OfficialSourceFacility::query()->count());
    }

    #[Test]
    public function district_snapshot_never_marks_other_facilities_in_province_stale(): void
    {
        $service = app(OfficialSourceMirrorService::class);
        $service->persist($this->batch(), $this->facilities());

        $districtBatch = $this->batch([
            'source_district_code' => 'DIST-01',
            'district_name' => 'Quận Test',
            'sync_scope' => 'district',
        ]);
        $service->persist($districtBatch, [$this->facilities()[0]]);
        $districtBatch->refresh();

        $otherFacility = OfficialSourceFacility::query()->where('external_id', '94170')->firstOrFail();

        $this->assertSame(0, $districtBatch->stale_count);
        $this->assertTrue($otherFacility->is_active);
    }

    #[Test]
    public function basic_listing_sync_does_not_erase_future_detail_enrichment(): void
    {
        $service = app(OfficialSourceMirrorService::class);
        $service->persist($this->batch(), [$this->facilities()[0]]);

        $facility = OfficialSourceFacility::query()->where('external_id', '93108')->firstOrFail();
        $facility->update([
            'source_details' => ['address' => 'Chi tiết nguồn'],
            'details_hash' => str_repeat('d', 64),
            'details_synced_at' => now(),
        ]);

        $service->persist($this->batch(), [$this->facilities()[0]]);
        $facility->refresh();

        $this->assertSame(['address' => 'Chi tiết nguồn'], $facility->source_details);
        $this->assertSame(str_repeat('d', 64), $facility->details_hash);
        $this->assertNotNull($facility->details_synced_at);
    }

    private function batch(array $overrides = []): OfficialSourceSyncBatch
    {
        return OfficialSourceSyncBatch::query()->create(array_merge([
            'source' => 'bhxh',
            'source_province_code' => '92TTT',
            'province_name' => 'Thành phố Cần Thơ',
            'sync_scope' => 'province',
            'status' => 'QUEUED',
        ], $overrides));
    }

    private function facilities(): array
    {
        return [
            ['external_id' => '93108', 'facility_name' => 'Trung tâm Y tế thành phố Ngã Bảy'],
            ['external_id' => '94170', 'facility_name' => 'Bệnh viện Quốc tế Phương Châu Sóc Trăng'],
        ];
    }
}
