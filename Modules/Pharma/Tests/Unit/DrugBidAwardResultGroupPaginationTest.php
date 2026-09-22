<?php

namespace Modules\Pharma\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Services\DrugBidAwardService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DrugBidAwardResultGroupPaginationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_paginates_result_groups_with_the_correct_total_and_aggregates(): void
    {
        $this->award([
            'bidding_notice_code' => 'TBMT-001',
            'medicine_name' => 'Medicine A',
            'quantity' => 10,
            'unit_price' => 100,
            'amount' => 1000,
            'published_at' => '2026-09-01 08:00:00',
        ]);

        $this->award([
            'bidding_notice_code' => 'TBMT-001',
            'medicine_name' => 'Medicine B',
            'quantity' => 20,
            'unit_price' => 200,
            'amount' => 4000,
            'published_at' => '2026-09-02 08:00:00',
        ]);

        $withoutTbmt = $this->award([
            'bidding_notice_code' => null,
            'medicine_name' => 'Medicine C',
            'quantity' => 5,
            'unit_price' => 300,
            'amount' => 1500,
            'published_at' => '2026-09-03 08:00:00',
        ]);

        $emptyTbmt = $this->award([
            'bidding_notice_code' => '',
            'medicine_name' => 'Medicine D',
            'quantity' => 2,
            'unit_price' => 400,
            'amount' => 800,
            'published_at' => '2026-09-04 08:00:00',
        ]);

        $paginator = app(DrugBidAwardService::class)
            ->getResultGroupsPaginated(perPage: 10, page: 1);

        $this->assertSame(3, $paginator->total());
        $this->assertCount(3, $paginator->items());

        $groups = $paginator->getCollection()->keyBy('result_key');

        $this->assertTrue($groups->has('TBMT-001'));
        $this->assertTrue($groups->has('award-'.$withoutTbmt->id));
        $this->assertTrue($groups->has('award-'.$emptyTbmt->id));

        $tbmt = $groups->get('TBMT-001');

        $this->assertSame(2, (int) $tbmt->product_count);
        $this->assertSame(30.0, (float) $tbmt->total_quantity);
        $this->assertSame(5000.0, (float) $tbmt->total_value);
    }

    #[Test]
    public function service_supplies_an_explicit_group_total_to_length_aware_pagination(): void
    {
        $service = file_get_contents(
            dirname(__DIR__, 2).'/Services/DrugBidAwardService.php'
        );

        $this->assertStringContainsString(
            "->fromSub(\$countQuery->toBase(), 'result_groups')",
            $service
        );
        $this->assertStringContainsString(
            "\$total,\n        );",
            $service
        );
    }

    private function award(array $overrides = []): DrugBidAward
    {
        static $sequence = 0;

        $sequence++;

        return DrugBidAward::query()->create(array_merge([
            'medicine_name' => 'Medicine '.$sequence,
            'packaging_specification' => 'Box',
            'quantity' => 1,
            'unit_price' => 100,
            'bidding_notice_code' => 'TBMT-'.$sequence,
            'investor_name' => 'Hospital '.$sequence,
            'decision_number' => 'QD-'.$sequence,
            'decision_date' => '2026-09-01',
            'contract_duration_months' => 12,
            'winning_company_name' => 'Company '.$sequence,
            'source_type' => DrugBidAward::SOURCE_MANUAL,
        ], $overrides));
    }
}
