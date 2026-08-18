<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Services\SyncedPricingExportPreferenceService;
use Tests\TestCase;

class SyncedPricingExportPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_column_order_selection_and_alignment_per_user(): void
    {
        $service = app(SyncedPricingExportPreferenceService::class);

        $saved = $service->save(
            99,
            ['ten_thuoc', 'gdklh_gpnk', 'nhom_thuoc'],
            ['gdklh_gpnk', 'ten_thuoc'],
            ['ten_thuoc' => 'center', 'gdklh_gpnk' => 'right'],
        );

        $this->assertSame(['ten_thuoc', 'gdklh_gpnk', 'nhom_thuoc'], array_slice($saved['column_order'], 0, 3));
        $this->assertSame(['ten_thuoc', 'gdklh_gpnk'], $saved['selected_columns']);
        $this->assertSame('center', $saved['alignments']['ten_thuoc']);
        $this->assertSame('right', $saved['alignments']['gdklh_gpnk']);

        $loaded = $service->forUser(99);

        $this->assertSame($saved['column_order'], $loaded['column_order']);
        $this->assertSame($saved['selected_columns'], $loaded['selected_columns']);
        $this->assertSame($saved['alignments'], $loaded['alignments']);
    }
}
