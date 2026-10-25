<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Finance\Livewire\DebtLedger;
use Tests\TestCase;

final class FinanceDebtLedgerUiTest extends TestCase
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

    public function test_ledger_filters_and_displays_adjustments_without_mutation(): void
    {
        $this->login(true);
        $tag = 'UI-LEDGER-'.uniqid();
        $first = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => $tag.'-A', 'counterparty_name' => $tag,
            'direction' => 'receivable', 'status' => 'confirmed',
            'original_amount' => '100.00', 'due_on' => '2026-10-20',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('finance_debt_obligations')->insert([
            'reference_number' => $tag.'-B', 'counterparty_name' => $tag,
            'direction' => 'payable', 'status' => 'draft',
            'original_amount' => '300.00', 'due_on' => '2026-11-20',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('finance_debt_events')->insert([
            'obligation_id' => $first, 'event_type' => 'adjustment',
            'signed_amount' => '-20.00', 'effective_on' => '2026-10-20',
            'reason' => 'UI ledger test', 'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::test(DebtLedger::class)->set('search', $tag)->assertOk()
            ->assertSee($tag.'-A')->assertSee('100 ₫')->assertSee('−20 ₫')->assertSee('80 ₫')
            ->assertDontSee($tag.'-B');
        Livewire::test(DebtLedger::class)->set('search', $tag)
            ->set('status', 'draft')->set('direction', 'payable')
            ->assertOk()->assertSee($tag.'-B')->assertDontSee($tag.'-A');
        Livewire::test(DebtLedger::class)->set('search', $tag)
            ->set('dateFrom', '2026-11-01')->assertDontSee($tag.'-A');
        self::assertSame(1, DB::table('finance_debt_events')->where('obligation_id', $first)->count());
    }

    public function test_ledger_requires_view_permission(): void
    {
        $this->login(false);
        Livewire::test(DebtLedger::class)->assertForbidden();
    }
}
