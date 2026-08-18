<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Muasamcong\Models\PricingResult;
use Tests\TestCase;

class SyncedPricingManualQuoteFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_synced_pricing_supports_manual_quote_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('muasamcong_pricing_results', [
            'stt_tt20_2022',
            'gia_kk_kkl',
            'don_gia_vat',
        ]));

        $item = PricingResult::query()->create([
            'source_id' => '42a99f97-1b14-486d-b7c4-37a13964eb33',
            'ten_thuoc' => 'Test medicine',
            'raw_payload' => [],
            'stt_tt20_2022' => '00125',
            'gia_kk_kkl' => 12345.5,
            'don_gia_vat' => 13580.05,
        ]);

        $item->refresh();

        $this->assertSame('00125', $item->stt_tt20_2022);
        $this->assertSame('12345.5000', $item->gia_kk_kkl);
        $this->assertSame('13580.0500', $item->don_gia_vat);
    }
}
