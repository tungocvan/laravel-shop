<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Finance\Livewire\DebtAging;
use Tests\TestCase;

final class FinanceDebtAgingUiTest extends TestCase
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

    public function test_aging_bucket_boundaries_filters_and_adjusted_principal(): void
    {
        $this->login(true);
        $tag = 'UI-AGING-'.uniqid();
        $rows = [
            ['FUTURE', 1, 'confirmed', 'receivable', '100.00'],
            ['TODAY', 0, 'confirmed', 'receivable', '200.00'],
            ['DAY30', -30, 'confirmed', 'receivable', '300.00'],
            ['DAY31', -31, 'confirmed', 'receivable', '400.00'],
            ['DAY60', -60, 'confirmed', 'receivable', '500.00'],
            ['DAY61', -61, 'confirmed', 'receivable', '600.00'],
            ['DAY90', -90, 'confirmed', 'receivable', '700.00'],
            ['DAY91', -91, 'confirmed', 'receivable', '800.00'],
            ['UNDATED', null, 'confirmed', 'payable', '900.00'],
            ['DRAFT', -5, 'draft', 'receivable', '999.00'],
        ];
        $ids = [];
        foreach ($rows as [$suffix, $days, $status, $direction, $amount]) {
            $ids[$suffix] = DB::table('finance_debt_obligations')->insertGetId([
                'reference_number' => $tag.'-'.$suffix,
                'counterparty_name' => $tag,
                'direction' => $direction,
                'status' => $status,
                'original_amount' => $amount,
                'due_on' => $days === null ? null : now()->startOfDay()->addDays($days)->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('finance_debt_events')->insert([
            'obligation_id' => $ids['DAY30'], 'event_type' => 'adjustment',
            'signed_amount' => '-20.00', 'effective_on' => now()->toDateString(),
            'reason' => 'Aging UI test', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $cases = [
            'upcoming' => 'FUTURE', 'due_today' => 'TODAY',
            'overdue_1_30' => 'DAY30', 'overdue_31_60' => 'DAY31',
            'overdue_61_90' => 'DAY61', 'overdue_90_plus' => 'DAY91',
            'undated' => 'UNDATED',
        ];
        foreach ($cases as $bucket => $suffix) {
            Livewire::test(DebtAging::class)->set('search', $tag)
                ->set('bucket', $bucket)->assertOk()
                ->assertSee($tag.'-'.$suffix)->assertDontSee($tag.'-DRAFT');
        }
        Livewire::test(DebtAging::class)->set('search', $tag)
            ->set('bucket', 'overdue_1_30')->assertSee('280 ₫')->assertDontSee($tag.'-DAY31');
        Livewire::test(DebtAging::class)->set('search', $tag)
            ->set('direction', 'payable')->assertSee($tag.'-UNDATED')->assertDontSee($tag.'-DAY30');
        self::assertSame(1, DB::table('finance_debt_events')->where('obligation_id', $ids['DAY30'])->count());
    }

    public function test_aging_requires_view_permission(): void
    {
        $this->login(false);
        Livewire::test(DebtAging::class)->assertForbidden();
    }
}
