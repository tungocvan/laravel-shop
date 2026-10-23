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
        $this->assertStringContainsString('Hiển thị chi tiết ngân hàng bổ sung', $settingsView);
        $this->assertStringContainsString('không ẩn dòng Tài khoản thu/chi chính', $settingsView);
        $this->assertStringContainsString('Hiển thị loại nghiệp vụ trên phiếu thủ công', $settingsView);
        $this->assertStringContainsString('Không áp dụng cho phiếu thu/chi tự động', $settingsView);
    }
    public function test_v2_print_controls_are_wired_from_form_to_pdf(): void
    {
        $base = dirname(__DIR__, 2);
        $defaults = require $base.'/config/print.php';
        $settings = file_get_contents($base.'/Services/FinancePrintSettings.php');
        $controller = file_get_contents($base.'/Http/Controllers/FinancePrintSettingsController.php');
        $form = file_get_contents($base.'/resources/views/vouchers/settings.blade.php');
        $pdf = file_get_contents($base.'/resources/views/vouchers/print-a5.blade.php');

        foreach (['creator_full_name', 'approver_full_name', 'show_creator_name',
            'show_approver_name', 'show_status', 'show_voucher_number',
            'show_category', 'show_amount_words', 'title_font_size', 'line_height'] as $key) {
            $this->assertArrayHasKey($key, $defaults['defaults']);
            $this->assertStringContainsString("'".$key."'", $settings);
            $this->assertStringContainsString("'".$key."'", $controller);
            $this->assertStringContainsString('name="'.$key.'"', $form);
            $this->assertStringContainsString("['".$key."']", $pdf);
        }

        $this->assertStringContainsString("format('d') }} tháng", $pdf);
        $this->assertStringContainsString("format('m') }} năm", $pdf);
        $this->assertStringNotContainsString('<p class="footer">Mẫu chứng từ nội bộ', $pdf);
        $this->assertStringContainsString("array_replace((require base_path('Modules/Finance/config/print.php')", $pdf);
    }

}
