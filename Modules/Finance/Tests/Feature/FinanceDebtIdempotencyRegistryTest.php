<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Services\FinanceDebtIdempotencyRegistry;
use Tests\TestCase;

final class FinanceDebtIdempotencyRegistryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
        if (! Schema::hasTable('finance_debt_idempotency_keys')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000002_create_finance_debt_idempotency_keys_table.php'))->up();
        }
    }

    private function debt(): int
    {
        return DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'IDEM-'.uniqid(),
            'direction' => 'receivable',
            'status' => 'draft',
            'counterparty_name' => 'Customer',
            'currency' => 'VND',
            'original_amount' => '100.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_reservation_is_persisted_without_settlement(): void
    {
        $id = $this->debt();
        $reservation = (new FinanceDebtIdempotencyRegistry)->reserve($id, 'post', 'reserve-unique-001');
        self::assertGreaterThan(0, $reservation);
        self::assertSame(1, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $id)->count());
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
    }

    public function test_duplicate_reservation_is_rejected_without_extra_row(): void
    {
        $id = $this->debt();
        $registry = new FinanceDebtIdempotencyRegistry;
        $registry->reserve($id, 'post', 'reserve-unique-002');
        try {
            $registry->reserve($id, 'post', 'reserve-unique-002');
            self::fail('Duplicate reservation was accepted.');
        } catch (ValidationException $exception) {
            self::assertSame(1, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $id)->count());
        }
    }

    public function test_same_key_can_be_scoped_to_distinct_operations(): void
    {
        $id = $this->debt();
        $registry = new FinanceDebtIdempotencyRegistry;
        $registry->reserve($id, 'post', 'reserve-unique-003');
        $registry->reserve($id, 'reverse', 'reserve-unique-003');
        self::assertSame(2, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $id)->count());
    }

    public function test_same_key_can_be_scoped_to_distinct_debts(): void
    {
        $registry = new FinanceDebtIdempotencyRegistry;
        $a = $this->debt();
        $b = $this->debt();
        $registry->reserve($a, 'post', 'reserve-unique-004');
        $registry->reserve($b, 'post', 'reserve-unique-004');
        self::assertSame(2, DB::table('finance_debt_idempotency_keys')->whereIn('obligation_id', [$a, $b])->count());
    }

    public function test_outer_transaction_rollback_removes_reservation(): void
    {
        $id = $this->debt();
        try {
            DB::transaction(function () use ($id): void {
                (new FinanceDebtIdempotencyRegistry)->reserve($id, 'post', 'reserve-unique-005');
                throw new \RuntimeException('simulate later failure');
            });
            self::fail('Expected rollback exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame(0, DB::table('finance_debt_idempotency_keys')->where('obligation_id', $id)->count());
        }
    }

    public function test_invalid_operation_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtIdempotencyRegistry)->reserve($this->debt(), 'delete', 'reserve-unique-006');
    }

    public function test_invalid_key_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new FinanceDebtIdempotencyRegistry)->reserve($this->debt(), 'post', 'bad key');
    }
}
