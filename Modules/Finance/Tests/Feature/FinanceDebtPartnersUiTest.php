<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Finance\Livewire\DebtPartners;
use Tests\TestCase;

final class FinanceDebtPartnersUiTest extends TestCase
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

    public function test_partner_screen_filters_and_aggregates_adjusted_principal_without_mutations(): void
    {
        $this->login(true);
        $tag = 'UI-PARTNER-'.uniqid();
        $ids = [];
        foreach ([['receivable', 'confirmed', '100.00'], ['receivable', 'confirmed', '200.00'], ['payable', 'draft', '300.00']] as [$direction, $status, $amount]) {
            $ids[] = DB::table('finance_debt_obligations')->insertGetId([
                'reference_number' => $tag.'-'.$direction.'-'.$amount,
                'counterparty_name' => $tag,
                'counterparty_tax_code' => '1234567890',
                'direction' => $direction, 'status' => $status,
                'original_amount' => $amount,
                'due_on' => now()->subDay()->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('finance_debt_events')->insert([
            'obligation_id' => $ids[0], 'event_type' => 'adjustment',
            'signed_amount' => '-20.00', 'effective_on' => now()->toDateString(),
            'reason' => 'UI correction', 'created_at' => now(), 'updated_at' => now(),
        ]);

        Livewire::test(DebtPartners::class)->set('search', $tag)
            ->assertOk()->assertSee($tag)->assertSee('280')->assertDontSee('300');
        Livewire::test(DebtPartners::class)->set('search', $tag)
            ->set('direction', 'payable')->set('status', 'draft')
            ->assertOk()->assertSee('300');
        self::assertSame(1, DB::table('finance_debt_events')->where('obligation_id', $ids[0])->count());
    }

    public function test_partner_screen_requires_view_permission(): void
    {
        $this->login(false);
        Livewire::test(DebtPartners::class)->assertForbidden();
    }
}
