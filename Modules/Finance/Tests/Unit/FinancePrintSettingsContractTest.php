<?php

namespace Modules\Finance\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class FinancePrintSettingsContractTest extends TestCase
{
    public function test_layout_configuration_has_safe_defaults_and_strict_validation(): void
    {
        $base = dirname(__DIR__, 2);
        $defaults = require $base.'/config/print.php';
        $service = file_get_contents($base.'/Services/FinancePrintSettings.php');
        $routes = file_get_contents($base.'/routes/web.php');
        $controller = file_get_contents($base.'/Http/Controllers/FinanceVoucherPrintController.php');
        $settingsView = file_get_contents($base.'/resources/views/vouchers/settings.blade.php');

        $this->assertSame('A5', $defaults['defaults']['paper']);
        $this->assertSame('portrait', $defaults['defaults']['orientation']);
        $this->assertStringContainsString('between:5,20', $service);
        $this->assertStringContainsString('between:9,14', $service);
        $this->assertStringContainsString('finance_print_settings', $service);
        $this->assertStringContainsString('Pdf::loadHTML', $controller);
        $this->assertStringContainsString('->download(', $controller);
        $this->assertStringContainsString('permission:finance.account.manage,admin', $routes);
        $this->assertStringContainsString('@csrf', $settingsView);
        $this->assertStringContainsString('name="paper"', $settingsView);
    }
}
