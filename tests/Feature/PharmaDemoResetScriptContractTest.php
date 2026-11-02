<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmaDemoResetScriptContractTest extends TestCase
{
    public function test_reset_script_is_local_only_interactive_and_transactional(): void
    {
        $script = file_get_contents(base_path('reset-pharma-demo.sh'));

        $this->assertStringContainsString("environment !== 'local'", $script);
        $this->assertStringContainsString("'127.0.0.1'", $script);
        $this->assertStringContainsString("information_schema.KEY_COLUMN_USAGE", $script);
        $this->assertStringContainsString('external FK', $script);
        $this->assertStringContainsString("fopen('/dev/tty', 'r+')", $script);
        $this->assertStringContainsString('BACKUP READY', $script);
        $this->assertStringContainsString('DB::transaction(', $script);
        $this->assertStringContainsString("if (\$mode === '--dry-run')", $script);
        $this->assertStringNotContainsString('FOREIGN_KEY_CHECKS=0', $script);
        $this->assertStringNotContainsString('TRUNCATE TABLE', $script);
        $this->assertStringNotContainsString('migrate:fresh', $script);
    }

    public function test_script_scopes_reset_to_inventory_and_commission_data(): void
    {
        $script = file_get_contents(base_path('reset-pharma-demo.sh'));
        foreach ([
            'pharma_inventory_issues',
            'pharma_inventory_receipts',
            'pharma_inventory_balances',
            'pharma_inventory_cost_adjustments',
            'pharma_inventory_transactions',
            'pharma_inventory_issue_commissions',
        ] as $table) {
            $this->assertStringContainsString("'{$table}'", $script);
        }
        $this->assertStringNotContainsString("DB::table('pharma_medicines')->delete()", $script);
    }
}
