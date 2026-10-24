<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Finance\Livewire\DebtWorkspace;
use Tests\TestCase;

final class FinanceDebtLivewireAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('finance_debt_obligations')) {
            (require base_path('Modules/Finance/database/migrations/2026_10_24_000001_create_finance_debt_foundation_tables.php'))->up();
        }
        config()->set('modules.registry.Partner.enabled', false);
    }

    private function authenticateWithPermissions(bool $canView, bool $canManage): void
    {
        // Exercise Livewire's actual action dispatch and the admin guard. The
        // permission decision is controlled explicitly to test both boundaries.
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 12345;
        $user->shouldReceive('can')->with('finance.view')->andReturn($canView);
        $user->shouldReceive('can')->with('finance.debt.manage')->andReturn($canManage);
        Auth::guard('admin')->setUser($user);
    }

    public function test_view_only_admin_can_read_but_cannot_open_or_submit_mutations(): void
    {
        $this->authenticateWithPermissions(true, false);
        Livewire::test(DebtWorkspace::class)->assertOk()->call('openCreate')->assertForbidden();
        Livewire::test(DebtWorkspace::class)->call('create')->assertForbidden();
        Livewire::test(DebtWorkspace::class)->call('choosePartner', 1)->assertForbidden();
        Livewire::test(DebtWorkspace::class)->call('searchPartners', 'buyer')->assertForbidden();
    }

    public function test_view_only_admin_cannot_trigger_partner_lookup_by_changing_direction(): void
    {
        $this->authenticateWithPermissions(true, false);

        Livewire::test(DebtWorkspace::class)
            ->set('draftDirection', 'payable')
            ->assertForbidden();
    }

    public function test_view_only_admin_cannot_confirm_or_adjust_even_with_selected_id(): void
    {
        $this->authenticateWithPermissions(true, false);
        $id = DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => 'AUTH-'.uniqid(),
            'direction' => 'receivable',
            'status' => 'draft',
            'counterparty_name' => 'Customer',
            'original_amount' => '100.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(DebtWorkspace::class)->set('selectedDebtId', $id)->call('confirm')->assertForbidden();
        Livewire::test(DebtWorkspace::class)->set('selectedDebtId', $id)->call('adjust')->assertForbidden();
        self::assertSame('draft', DB::table('finance_debt_obligations')->where('id', $id)->value('status'));
        self::assertSame(0, DB::table('finance_debt_events')->where('obligation_id', $id)->count());
    }

    public function test_manager_can_open_draft_form_but_user_without_view_is_denied(): void
    {
        $this->authenticateWithPermissions(true, true);
        Livewire::test(DebtWorkspace::class)->call('openCreate')->assertOk()->assertSet('creating', true);

        $this->authenticateWithPermissions(false, true);
        Livewire::test(DebtWorkspace::class)->assertForbidden();
    }
}
