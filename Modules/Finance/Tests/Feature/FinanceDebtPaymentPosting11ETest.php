<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use App\Models\User;
use Modules\Finance\Livewire\DebtWorkspace;
use Modules\Finance\Services\FinanceDebtPaymentPostingService;
use Modules\Finance\Services\FinanceDebtPaymentEvidenceReader;
use Tests\TestCase;

final class FinanceDebtPaymentPosting11ETest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $migrations = [
            ['finance_accounts', '2026_10_21_000001_create_finance_foundation_tables.php'],
            ['finance_debt_obligations', '2026_10_24_000001_create_finance_debt_foundation_tables.php'],
            ['finance_debt_idempotency_keys', '2026_10_24_000002_create_finance_debt_idempotency_keys_table.php'],
            ['finance_debt_settlement_mappings', '2026_10_24_000003_create_finance_debt_settlement_mappings_table.php'],
            ['finance_debt_allocation_ownerships', '2026_10_24_000004_create_finance_debt_allocation_ownerships_table.php'],
            ['finance_debt_settlement_audit_events', '2026_10_24_000005_create_finance_debt_settlement_audit_events_table.php'],
        ];
        foreach ($migrations as [$table, $file]) {
            if (! Schema::hasTable($table)) {
                (require base_path('Modules/Finance/database/migrations/'.$file))->up();
            }
        }
        if (! Schema::hasColumn('finance_vouchers', 'origin')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_21_000003_add_manual_fields_to_finance_vouchers.php'))->up();
        }
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table): void {
                $table->id();
                $table->string('invoice_type');
                $table->decimal('total_amount', 18, 2);
                $table->string('invoice_number')->nullable();
            });
        }
    }

    private function seedDebt(string $direction = 'receivable'): array
    {
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_type' => $direction === 'receivable' ? 'sold' : 'purchase',
            'total_amount' => '40824000.00',
            'invoice_number' => '11E-'.uniqid(),
        ]);
        $debtId = DB::table('finance_debt_obligations')->insertGetId([
            'source_module' => 'Invoices', 'source_type' => 'invoice',
            'source_document_id' => (string) $invoiceId,
            'reference_number' => '11E-DEBT-'.uniqid(),
            'direction' => $direction, 'status' => 'confirmed',
            'counterparty_name' => '11E Demo', 'currency' => 'VND',
            'original_amount' => '40824000.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $accountId = DB::table('finance_accounts')->insertGetId([
            'code' => '11E-'.uniqid(), 'name' => '11E Demo Account',
            'kind' => 'bank', 'currency' => 'VND', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return [$debtId, $accountId];
    }

    public function test_two_installments_post_to_ledger_and_settle_exactly_once(): void
    {
        [$debt, $account] = $this->seedDebt();
        $service = app(FinanceDebtPaymentPostingService::class);
        $first = $service->post($debt, $account, '20000000.00', '2026-10-25', 'BANK-11E-A', 123);
        self::assertSame('partial', $first['status']);
        self::assertSame('20824000.00', $first['remaining']);
        $second = $service->post($debt, $account, '20824000.00', '2026-10-25', 'BANK-11E-B', 123);
        self::assertSame('paid', $second['status']);
        self::assertSame('0.00', $second['remaining']);
        self::assertSame(2, DB::table('finance_vouchers')->whereIn('id', [$first['voucher_id'], $second['voucher_id']])->where('status', 'posted')->count());
        self::assertSame(2, DB::table('finance_ledger_entries')->whereIn('voucher_id', [$first['voucher_id'], $second['voucher_id']])->count());
        self::assertSame(2, DB::table('finance_debt_allocation_ownerships')->where('obligation_id', $debt)->count());
        self::assertSame(2, DB::table('finance_debt_settlement_audit_events')->where('obligation_id', $debt)->count());
        $evidence = app(FinanceDebtPaymentEvidenceReader::class)->forObligation($debt);
        self::assertSame('Đã thanh toán', $evidence['payment_status']);
        self::assertSame('0.00', $evidence['verified_outstanding']);
        try {
            $service->post($debt, $account, '100.00', '2026-10-25', 'BANK-11E-C', 123);
            self::fail('Overpayment must be rejected.');
        } catch (ValidationException $expected) {
            self::assertSame(2, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $debt)->count());
        }
    }

    public function test_livewire_confirms_payment_only_with_manage_permission(): void
    {
        [$debt, $account] = $this->seedDebt();
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 12345;
        $user->shouldReceive('can')->with('finance.view')->andReturn(true);
        $user->shouldReceive('can')->with('finance.debt.manage')->andReturn(true);
        Auth::guard('admin')->setUser($user);

        Livewire::test(DebtWorkspace::class)
            ->call('setTab', 'debts')
            ->call('openPaymentReview', $debt)
            ->set('paymentAmount', '20.000.000')
            ->set('paymentAccountId', (string) $account)
            ->set('paymentReference', '11E-LIVEWIRE-REF')
            ->set('paymentDate', '2026-10-25')
            ->call('previewPayment')
            ->assertSet('paymentPreviewed', true)
            ->call('confirmPayment')
            ->assertSet('paymentReviewDebtId', null);
        self::assertSame(1, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $debt)->count());
        self::assertSame(1, DB::table('finance_ledger_entries as l')
            ->join('finance_debt_settlement_mappings as m', 'm.voucher_id', '=', 'l.voucher_id')
            ->where('m.obligation_id', $debt)->count());
    }

    public function test_duplicate_reference_is_rejected_without_another_voucher(): void
    {
        [$debt, $account] = $this->seedDebt('payable');
        $service = app(FinanceDebtPaymentPostingService::class);
        $first = $service->post($debt, $account, '1000000.00', '2026-10-25', 'BANK-11E-DUP', 123);
        self::assertEqualsWithDelta(-1000000.00, (float) DB::table('finance_ledger_entries')->where('voucher_id', $first['voucher_id'])->value('signed_amount'), 0.00001);
        $this->expectException(ValidationException::class);
        $service->post($debt, $account, '1000000.00', '2026-10-25', 'BANK-11E-DUP', 123);
    }
}
