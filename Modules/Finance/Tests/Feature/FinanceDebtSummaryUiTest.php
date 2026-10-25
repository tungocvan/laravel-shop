<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Finance\Livewire\DebtSummary;
use Tests\TestCase;

final class FinanceDebtSummaryUiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
    }

    private function login(bool $allowed): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 12345;
        $user->shouldReceive('can')->with('finance.view')->andReturn($allowed);
        Auth::guard('admin')->setUser($user);
    }

    public function test_summary_groups_confirmed_debts_and_filters_recognition_cohort(): void
    {
        $this->login(true);
        $tag = 'UI-SUMMARY-'.uniqid();
        $ids = [];
        foreach ([
            ['receivable', 'confirmed', '100.00', '2026-10-20'],
            ['receivable', 'confirmed', '200.00', '2026-10-21'],
            ['payable', 'confirmed', '400.00', '2026-09-20'],
            ['receivable', 'draft', '700.00', '2026-10-20'],
        ] as [$direction, $status, $amount, $recognized]) {
            $ids[] = DB::table('finance_debt_obligations')->insertGetId([
                'reference_number' => $tag.'-'.$amount,
                'counterparty_name' => $tag,
                'counterparty_tax_code' => '1234567890',
                'direction' => $direction,
                'status' => $status,
                'original_amount' => $amount,
                'recognized_on' => $recognized,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('finance_debt_events')->insert([
            'obligation_id' => $ids[0],
            'event_type' => 'adjustment',
            'signed_amount' => '-20.00',
            'effective_on' => '2026-10-24',
            'reason' => 'Summary UI test',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::test(DebtSummary::class)->set('search', $tag)->assertOk()
            ->assertSee('280 ₫')->assertSee('400 ₫')->assertDontSee('700 ₫');
        Livewire::test(DebtSummary::class)->set('search', $tag)
            ->set('dateFrom', '2026-10-01')->set('dateTo', '2026-10-31')
            ->assertOk()->assertSee('280 ₫')->assertDontSee('400 ₫');
        Livewire::test(DebtSummary::class)->set('search', $tag)
            ->set('direction', 'payable')->assertOk()->assertSee('400 ₫')->assertDontSee('280 ₫');
        self::assertSame(1, DB::table('finance_debt_events')->where('obligation_id', $ids[0])->count());
    }

    public function test_summary_requires_finance_view_permission(): void
    {
        $this->login(false);
        Livewire::test(DebtSummary::class)->assertForbidden();
    }
}
