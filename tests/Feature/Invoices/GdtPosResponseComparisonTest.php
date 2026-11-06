<?php

namespace Tests\Feature\Invoices;

use Modules\Invoices\Services\GdtPosResponseComparison;
use PHPUnit\Framework\TestCase;

class GdtPosResponseComparisonTest extends TestCase
{
    public function test_disjoint_page_two_completes_seventeen_unique_invoices(): void
    {
        $first = ['datas' => array_map(fn ($i) => ['id' => "invoice-{$i}"], range(1, 15)), 'total' => 17, 'state' => 'opaque'];
        $second = ['datas' => [['id' => 'invoice-16'], ['id' => 'invoice-17']], 'total' => 17];

        $result = GdtPosResponseComparison::compare($first, $second);

        $this->assertSame(15, $result['first']['datas_count']);
        $this->assertSame(2, $result['second']['datas_count']);
        $this->assertSame(0, $result['shared_id_count']);
        $this->assertSame(2, $result['new_id_count']);
        $this->assertSame(17, $result['union_unique_id_count']);
        $this->assertTrue($result['complete_unique_set']);
        $this->assertTrue($result['first']['has_state']);
        $this->assertFalse($result['second']['has_state']);
        $this->assertStringNotContainsString('invoice-16', json_encode($result));
        $this->assertStringNotContainsString('opaque', json_encode($result));
    }

    public function test_repeated_fifteen_item_response_is_not_mistaken_for_completion(): void
    {
        $page = ['datas' => array_map(fn ($i) => ['id' => "invoice-{$i}"], range(1, 15)), 'total' => 17, 'state' => 'opaque'];

        $result = GdtPosResponseComparison::compare($page, $page);

        $this->assertSame(15, $result['shared_id_count']);
        $this->assertSame(0, $result['new_id_count']);
        $this->assertSame(15, $result['union_unique_id_count']);
        $this->assertFalse($result['complete_unique_set']);
    }

    public function test_overlapping_response_only_counts_new_ids(): void
    {
        $first = ['datas' => array_map(fn ($i) => ['id' => "invoice-{$i}"], range(1, 15)), 'total' => 17];
        $second = ['datas' => array_map(fn ($i) => ['id' => "invoice-{$i}"], range(3, 17)), 'total' => 17];

        $result = GdtPosResponseComparison::compare($first, $second);

        $this->assertSame(13, $result['shared_id_count']);
        $this->assertSame(2, $result['new_id_count']);
        $this->assertSame(17, $result['union_unique_id_count']);
        $this->assertTrue($result['complete_unique_set']);
    }

    public function test_invalid_shape_or_missing_invoice_id_fails_closed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GdtPosResponseComparison::compare(
            ['datas' => [['id' => 'invoice-1']], 'total' => 2],
            ['datas' => [['other' => 'value']], 'total' => 2],
        );
    }

    public function test_mismatched_totals_do_not_mark_complete(): void
    {
        $result = GdtPosResponseComparison::compare(
            ['datas' => [['id' => 'a']], 'total' => 2],
            ['datas' => [['id' => 'b']], 'total' => 3],
        );

        $this->assertFalse($result['totals_match']);
        $this->assertFalse($result['complete_unique_set']);
    }
}
