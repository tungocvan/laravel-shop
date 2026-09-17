<?php

declare(strict_types=1);

namespace Modules\System\Tests\Unit;

use Modules\System\Services\Database\ModuleSchemaRepairPlanner;
use PHPUnit\Framework\TestCase;

class ModuleSchemaRepairPlannerTest extends TestCase
{
    public function test_equivalent_index_with_different_name_is_safe_rename_plan(): void
    {
        $definition = [['column' => 'medicine_id', 'sequence' => 1, 'unique' => false]];
        $report = ['issues' => [[
            'table' => 'pharma_drug_bid_awards',
            'type' => 'indexes_changed',
            'risk' => 'REVIEW',
            'snapshot_definition' => ['pharma_drug_bid_awards_medicine_id_foreign' => $definition],
            'current_definition' => ['medicine_id' => $definition],
            'differences' => [
                'missing' => ['pharma_drug_bid_awards_medicine_id_foreign'],
                'extra' => ['medicine_id'],
            ],
        ]]];

        $plan = (new ModuleSchemaRepairPlanner)->plan($report);

        self::assertTrue($plan['all_safe']);
        self::assertFalse($plan['execution_available']);
        self::assertSame('SAFE', $plan['steps'][0]['risk']);
        self::assertSame('RENAME_INDEX', $plan['steps'][0]['action']);
        self::assertSame('medicine_id', $plan['steps'][0]['from']);
        self::assertSame('pharma_drug_bid_awards_medicine_id_foreign', $plan['steps'][0]['to']);
    }

    public function test_missing_index_without_equivalent_requires_review_with_evidence_boundary(): void
    {
        $report = ['issues' => [[
            'table' => 'pharma_drug_bid_awards',
            'type' => 'indexes_changed',
            'risk' => 'REVIEW',
            'snapshot_definition' => ['expected_index' => [['column' => 'medicine_id', 'sequence' => 1, 'unique' => false]]],
            'current_definition' => [],
            'differences' => ['missing' => ['expected_index']],
        ]]];

        $plan = (new ModuleSchemaRepairPlanner)->plan($report);

        self::assertFalse($plan['all_safe']);
        self::assertSame('REVIEW', $plan['steps'][0]['risk']);
        self::assertContains($plan['steps'][0]['action'], ['ADD_INDEX_REVIEW', 'INDEX_EVIDENCE_CONFLICT_REVIEW']);
        self::assertSame(['medicine_id'], $plan['steps'][0]['evidence']['columns']);
        self::assertArrayHasKey('matching_foreign_keys', $plan['steps'][0]['evidence']);
        self::assertArrayHasKey('migration_candidates', $plan['steps'][0]['evidence']);
        self::assertFalse($plan['execution_available']);
    }
}
