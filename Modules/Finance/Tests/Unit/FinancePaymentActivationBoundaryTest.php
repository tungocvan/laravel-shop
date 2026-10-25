<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Checkpoint 11A: ensure the UI does not advertise an unimplemented
 * settlement command and existing treasury posting cannot be silently
 * repurposed for debt payments.
 */
final class FinancePaymentActivationBoundaryTest extends TestCase
{
    public function test_payment_remains_disabled_until_authoritative_debt_mapping_is_wired(): void
    {
        $root = dirname(__DIR__, 4);
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');
        $gate = file_get_contents($root.'/Modules/Finance/Services/FinanceDebtAtomicSettlementGate.php');
        $writer = file_get_contents($root.'/Modules/Finance/Services/FinanceDebtInternalSettlementWriter.php');

        self::assertStringContainsString('chưa trừ thanh toán', $view);
        self::assertStringContainsString('Atomic settlement blocked:', $gate);
        self::assertStringContainsString('NOT exposed by a route/controller', $writer);
        self::assertStringNotContainsString('wire:click="confirmPayment"', $view);
    }

    public function test_existing_voucher_allocation_schema_must_be_reused_not_duplicated(): void
    {
        $root = dirname(__DIR__, 4);
        $foundation = file_get_contents($root.'/Modules/Finance/database/migrations/2026_10_21_000001_create_finance_foundation_tables.php');
        $mapping = file_get_contents($root.'/Modules/Finance/database/migrations/2026_10_24_000003_create_finance_debt_settlement_mappings_table.php');

        self::assertStringContainsString("Schema::create('finance_vouchers'", $foundation);
        self::assertStringContainsString("Schema::create('finance_allocations'", $foundation);
        self::assertStringContainsString("Schema::create('finance_debt_settlement_mappings'", $mapping);
    }
}
