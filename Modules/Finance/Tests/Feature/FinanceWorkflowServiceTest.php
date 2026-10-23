<?php

namespace Modules\Finance\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Contracts\FinancialDocumentProvider;
use Modules\Finance\Data\FinancialDocumentSnapshot;
use Modules\Finance\Services\FinanceWorkflowService;
use Modules\Finance\Services\FinancialDocumentRegistry;
use Modules\Finance\Services\VoucherPostingService;
use Tests\TestCase;

final class FinanceWorkflowServiceTest extends TestCase
{
    private FinanceWorkflowService $workflow;
    private VoucherPostingService $posting;

    protected function setUp(): void
    {
        parent::setUp();
        if (! \Illuminate\Support\Facades\Schema::hasTable('finance_accounts')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_21_000001_create_finance_foundation_tables.php'))->up();
        }

        $registry = new FinancialDocumentRegistry;
        $registry->register(new class implements FinancialDocumentProvider {
            public function module(): string { return 'Pharma'; }
            public function documentType(): string { return 'inventory_issue'; }
            public function resolveForPosting(string $documentId): FinancialDocumentSnapshot
            {
                return new FinancialDocumentSnapshot(
                    'Pharma', 'inventory_issue', $documentId,
                    'PX-WORKFLOW-'.$documentId, 'receivable', 'VND', '250.00', 'Customer'
                );
            }
        });
        $this->workflow = new FinanceWorkflowService($registry);
        $this->posting = new VoucherPostingService($registry);
    }

    public function test_create_post_partial_receipt_and_read_remaining_balance(): void
    {
        $account = $this->workflow->createAccount('CASH-'.uniqid(), 'Cash', 'cash');
        $source = (string) random_int(100000, 999999);
        $voucher = $this->workflow->createDraft('Pharma', 'inventory_issue', $source, $account, '100.00', 'PT-'.uniqid());
        $this->assertDatabaseHas('finance_vouchers', ['id' => $voucher, 'status' => 'draft', 'direction' => 'receipt']);
        $this->posting->post($voucher, null);

        $balance = $this->workflow->outstanding('Pharma', 'inventory_issue', $source);
        $this->assertSame('250.00', $balance['total']);
        $this->assertSame('100.00', $balance['paid']);
        $this->assertSame('150.00', $balance['remaining']);

        $second = $this->workflow->createDraft('Pharma', 'inventory_issue', $source, $account, '150.00', 'PT-'.uniqid());
        $this->posting->post($second, null);
        $this->assertSame('0.00', $this->workflow->outstanding('Pharma', 'inventory_issue', $source)['remaining']);
    }

    public function test_draft_rejects_amount_greater_than_document_total(): void
    {
        $account = $this->workflow->createAccount('CASH-'.uniqid(), 'Cash', 'cash');
        $this->expectException(ValidationException::class);
        $this->workflow->createDraft('Pharma', 'inventory_issue', (string) random_int(100000, 999999), $account, '251.00', 'PT-'.uniqid());
    }

    public function test_drafts_reserve_balance_and_release_it_when_cancelled(): void
    {
        $account = $this->workflow->createAccount('CASH-'.uniqid(), 'Cash', 'cash');
        $source = (string) random_int(100000, 999999);
        $first = $this->workflow->createDraft('Pharma', 'inventory_issue', $source, $account, '200.00', 'PT-'.uniqid());
        $balance = $this->workflow->outstanding('Pharma', 'inventory_issue', $source);
        $this->assertSame('200.00', $balance['reserved']);
        $this->assertSame('50.00', $balance['available']);

        try {
            $this->workflow->createDraft('Pharma', 'inventory_issue', $source, $account, '51.00', 'PT-'.uniqid());
            $this->fail('Expected over-reservation to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $this->workflow->updateDraft($first, $account, '100.00');
        $this->assertSame('150.00', $this->workflow->outstanding('Pharma', 'inventory_issue', $source)['available']);
        $this->workflow->cancelDraft($first);
        $this->assertDatabaseHas('finance_vouchers', ['id' => $first, 'status' => 'cancelled']);
        $this->assertSame('250.00', $this->workflow->outstanding('Pharma', 'inventory_issue', $source)['available']);
    }

    public function test_manual_receipt_can_be_edited_posted_and_reversed_without_source_allocation(): void
    {
        $this->ensureManualColumns();
        $account = $this->workflow->createAccount('CASH-'.uniqid(), 'Cash', 'cash');
        $id = $this->workflow->createManualDraft(
            'receipt', 'deposit', 'Customer A', 'Receive deposit', $account,
            '1000000.00', 'MAN-'.uniqid(), '2026-10-21'
        );
        $this->assertDatabaseHas('finance_vouchers', ['id' => $id, 'origin' => 'manual', 'status' => 'draft']);
        $this->assertSame(0, DB::table('finance_allocations')->where('voucher_id', $id)->count());
        $this->workflow->updateManualDraft($id, $account, '900000.00', 'deposit', 'Customer A', 'Adjusted deposit', '2026-10-21');
        $this->posting->post($id, null);
        $this->assertDatabaseHas('finance_ledger_entries', [
            'voucher_id' => $id, 'entry_type' => 'posting', 'signed_amount' => '900000.00',
        ]);
        $this->posting->reverse($id, null);
        $this->assertDatabaseHas('finance_vouchers', ['id' => $id, 'status' => 'reversed']);
        $this->assertDatabaseHas('finance_ledger_entries', [
            'voucher_id' => $id, 'entry_type' => 'reversal', 'signed_amount' => '-900000.00',
        ]);
    }

    public function test_manual_payment_rejects_missing_reason_and_posts_negative_ledger(): void
    {
        $this->ensureManualColumns();
        $account = $this->workflow->createAccount('BANK-'.uniqid(), 'Bank', 'bank');
        try {
            $this->workflow->createManualDraft('payment', 'hospitality', 'Vendor', '', $account, '500.00', 'MAN-'.uniqid(), '2026-10-21');
            $this->fail('Missing description must fail.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
        $id = $this->workflow->createManualDraft('payment', 'hospitality', 'Vendor', 'Client meeting', $account, '500.00', 'MAN-'.uniqid(), '2026-10-21');
        $this->posting->post($id, null);
        $this->assertDatabaseHas('finance_ledger_entries', ['voucher_id' => $id, 'signed_amount' => '-500.00']);
    }

    public function test_manual_draft_can_be_cancelled_without_ledger(): void
    {
        $this->ensureManualColumns();
        $account = $this->workflow->createAccount('CASH-'.uniqid(), 'Cash', 'cash');
        $id = $this->workflow->createManualDraft('payment', 'advance', 'Staff', 'Staff advance', $account, '250.00', 'MAN-'.uniqid(), '2026-10-21');
        $this->workflow->cancelDraft($id);
        $this->assertDatabaseHas('finance_vouchers', ['id' => $id, 'status' => 'cancelled']);
        $this->assertSame(0, DB::table('finance_ledger_entries')->where('voucher_id', $id)->count());
    }

    private function ensureManualColumns(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('finance_vouchers', 'origin')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_21_000003_add_manual_fields_to_finance_vouchers.php'))->up();
        }
    }

    public function test_account_rejects_unknown_kind(): void
    {
        $this->expectException(ValidationException::class);
        $this->workflow->createAccount('X-'.uniqid(), 'Bad', 'wallet');
    }
}
