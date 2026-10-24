<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinanceDebtPartnerPickerContractTest extends TestCase
{
    public function test_form_uses_canonical_partner_selection_instead_of_free_text(): void
    {
        $root = dirname(__DIR__, 4);
        $view = file_get_contents($root.'/Modules/Finance/resources/views/livewire/debt-workspace.blade.php');
        $picker = file_get_contents($root.'/Modules/Finance/resources/views/livewire/partials/partner-picker.blade.php');
        $component = file_get_contents($root.'/Modules/Finance/Livewire/DebtWorkspace.php');
        $service = file_get_contents($root.'/Modules/Finance/Services/PartnerDebtDraftService.php');

        self::assertStringContainsString('partner-picker', $view);
        self::assertStringNotContainsString('wire:model="counterparty"', $view);
        self::assertStringContainsString('<x-pwa-select-search', $picker);
        self::assertStringContainsString('data-pwa-select-search-input', $picker);
        self::assertStringContainsString('partnerId', $component);
        self::assertStringContainsString('finance-debt-partner-search', $component);
        self::assertStringContainsString('withPartnerType($role)', $component);
        self::assertStringContainsString('->limit(20)', $component);
        self::assertStringContainsString("status !== 'active'", $service);
        self::assertStringContainsString('hasPartnerType($role)', $service);
        self::assertStringContainsString("'partner_id' =>", $service);
        self::assertStringContainsString("'counterparty_tax_code' =>", $service);
    }
}
