<?php

namespace Modules\Finance\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\Finance\Livewire\DebtSettings;
use Tests\TestCase;

final class FinanceDebtSettingsUiTest extends TestCase
{
    private function login(bool $allowed): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 12345;
        $user->shouldReceive('can')->with('finance.view')->andReturn($allowed);
        Auth::guard('admin')->setUser($user);
    }

    public function test_settings_screen_displays_capabilities_and_limits_without_edit_controls(): void
    {
        $this->login(true);
        Livewire::test(DebtSettings::class)->assertOk()
            ->assertSee('Trạng thái chức năng và quy tắc công nợ')
            ->assertSee('Đồng bộ hóa đơn')
            ->assertSee('Thanh toán / đối trừ công nợ')
            ->assertSee('Số dư đầu kỳ / cuối kỳ kế toán')
            ->assertSee('Chưa kích hoạt')
            ->assertSee('finance.debt.manage')
            ->assertDontSee('wire:click="save"', false);
    }

    public function test_settings_screen_requires_finance_view_permission(): void
    {
        $this->login(false);
        Livewire::test(DebtSettings::class)->assertForbidden();
    }
}
