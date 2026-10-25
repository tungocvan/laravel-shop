<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Finance\Livewire\DebtDashboard;
use Modules\Finance\Livewire\DebtWorkspace;
use Tests\TestCase;

final class FinanceDebtDashboardUiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    private function login(bool $allowed = true): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 12345;
        $user->shouldReceive('can')->with('finance.view')->andReturn($allowed);
        $user->shouldReceive('can')->with('finance.debt.manage')->andReturn(false);
        Auth::guard('admin')->setUser($user);
    }

    private function debt(string $reference, string $direction, string $status, string $amount, ?string $due): int
    {
        return DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => $reference, 'counterparty_name' => 'UI Demo Partner',
            'direction' => $direction, 'status' => $status, 'original_amount' => $amount,
            'due_on' => $due, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_dashboard_uses_adjusted_principal_and_filters_without_posting_anything(): void
    {
        $this->login();
        $a = $this->debt('DASH-UI-A-'.uniqid(), 'receivable', 'confirmed', '100.00', now()->subDay()->toDateString());
        $b = $this->debt('DASH-UI-B-'.uniqid(), 'payable', 'confirmed', '200.00', now()->addDay()->toDateString());
        $draft = $this->debt('DASH-UI-C-'.uniqid(), 'receivable', 'draft', '300.00', null);
        DB::table('finance_debt_events')->insert([
            'obligation_id' => $a, 'event_type' => 'adjustment', 'signed_amount' => '-20.00',
            'effective_on' => now()->toDateString(), 'reason' => 'Correction',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $screen = Livewire::test(DebtDashboard::class)->assertOk()
            ->assertSee((string) $a)->assertDontSee('DASH-UI-C-');
        $screen->set('direction', 'receivable')->assertOk()
            ->assertSee('80')->assertDontSee('DASH-UI-B-');
        $screen->set('status', 'draft')->assertOk()->assertSee('DASH-UI-C-');
        self::assertSame('draft', DB::table('finance_debt_obligations')->where('id', $draft)->value('status'));
        self::assertSame(1, DB::table('finance_debt_events')->where('obligation_id', $a)->count());
    }

    public function test_dashboard_shows_separate_draft_workload_without_inflating_confirmed_principal(): void
    {
        $this->login();
        $ref = 'DASH-INBOX-'.uniqid();
        $this->debt($ref, 'payable', 'draft', '999.00', null);
        Livewire::test(DebtDashboard::class)->assertOk()
            ->assertSee('Việc cần xử lý')
            ->assertSee('Hóa đơn chưa ghi nhận')
            ->assertSee('Công nợ nháp')
            ->assertDontSee($ref);
    }

    public function test_dashboard_is_denied_without_finance_view_permission(): void
    {
        $this->login(false);
        Livewire::test(DebtDashboard::class)->assertForbidden();
    }

    public function test_documents_deep_link_selects_existing_debt_only(): void
    {
        $this->login();
        $id = $this->debt('DASH-LINK-'.uniqid(), 'receivable', 'draft', '50.00', null);
        Livewire::withQueryParams(['debt' => (string) $id])
            ->test(DebtWorkspace::class)
            ->assertOk()
            ->assertSet('activeTab', 'debts')
            ->assertSet('selectedDebtId', $id)
            ->assertSee('aria-modal="true"', false)
            ->call('closeDebt')
            ->assertSet('selectedDebtId', null);
    }
    public function test_confirmed_debt_payment_review_is_read_only_and_draft_is_not_payable(): void
    {
        $this->login();
        $confirmed = $this->debt('PAY-REVIEW-'.uniqid(), 'payable', 'confirmed', '125000.00', null);
        $draft = $this->debt('PAY-DRAFT-'.uniqid(), 'receivable', 'draft', '100.00', null);

        $screen = Livewire::test(DebtWorkspace::class)->call('setTab', 'debts')
            ->call('openPaymentReview', $confirmed)
            ->assertSet('paymentReviewDebtId', $confirmed)
            ->assertSee('Kiểm tra trước khi thanh toán')
            ->assertSee('Chưa thể xác nhận đã thanh toán')
            ->assertSee('Đối chiếu lịch sử thanh toán')
            ->assertSee('Chưa xác minh thanh toán')
            ->assertSee('aria-modal="true"', false)
            ->call('closePaymentReview')
            ->assertSet('paymentReviewDebtId', null);

        $screen->call('openPaymentReview', $draft)->assertNotFound();
        self::assertSame('confirmed', DB::table('finance_debt_obligations')->where('id', $confirmed)->value('status'));
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $confirmed)->count());
    }

}
