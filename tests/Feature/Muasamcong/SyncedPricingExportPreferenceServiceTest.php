<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Services\SyncedPricingExportPreferenceService;
use Tests\TestCase;

class SyncedPricingExportPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_column_order_selection_alignment_type_and_pixel_width_per_user(): void
    {
        $service = app(SyncedPricingExportPreferenceService::class);

        $saved = $service->save(
            99,
            ['ten_thuoc', 'gdklh_gpnk', 'nhom_thuoc'],
            ['gdklh_gpnk', 'ten_thuoc'],
            ['ten_thuoc' => 'center', 'gdklh_gpnk' => 'right'],
            ['ten_thuoc' => 220, 'gdklh_gpnk' => 180],
            ['ten_thuoc' => 'auto', 'gdklh_gpnk' => 'string'],
        );

        $this->assertSame(['ten_thuoc', 'gdklh_gpnk', 'nhom_thuoc'], array_slice($saved['column_order'], 0, 3));
        $this->assertSame(['ten_thuoc', 'gdklh_gpnk'], $saved['selected_columns']);
        $this->assertSame('center', $saved['alignments']['ten_thuoc']);
        $this->assertSame('right', $saved['alignments']['gdklh_gpnk']);
        $this->assertSame(220, $saved['widths']['ten_thuoc']);
        $this->assertSame(180, $saved['widths']['gdklh_gpnk']);
        $this->assertSame('auto', $saved['data_types']['ten_thuoc']);
        $this->assertSame('string', $saved['data_types']['gdklh_gpnk']);

        $loaded = $service->forUser(99);

        $this->assertSame($saved['column_order'], $loaded['column_order']);
        $this->assertSame($saved['selected_columns'], $loaded['selected_columns']);
        $this->assertSame($saved['alignments'], $loaded['alignments']);
        $this->assertSame($saved['widths'], $loaded['widths']);
        $this->assertSame($saved['data_types'], $loaded['data_types']);
    }

    public function test_pixel_widths_are_clamped_to_supported_bounds(): void
    {
        $service = app(SyncedPricingExportPreferenceService::class);

        $saved = $service->save(
            100,
            ['stt', 'ten_thuoc'],
            ['stt', 'ten_thuoc'],
            [],
            ['stt' => 20, 'ten_thuoc' => 900],
            [],
        );

        $this->assertSame(40, $saved['widths']['stt']);
        $this->assertSame(600, $saved['widths']['ten_thuoc']);
    }
}
