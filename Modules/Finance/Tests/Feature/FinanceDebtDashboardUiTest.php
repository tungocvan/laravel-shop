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
        self::assertSame(0, DB::table('finance_debt_settlement_mappings')->where('obligation_id', $a)->count());
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
        $this->app['request']->query->set('debt', (string) $id);
        Livewire::test(DebtWorkspace::class)->assertOk()->assertSet('selectedDebtId', $id);
    }
}
