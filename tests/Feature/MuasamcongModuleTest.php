<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Muasamcong\Exports\HsmtExport;
use Modules\Muasamcong\Livewire\ConfigManager;
use Modules\Muasamcong\Services\MuaSamCongService;
use Tests\TestCase;

class MuasamcongModuleTest extends TestCase
{
    public function test_config_ui_does_not_hydrate_token_or_cookie_into_livewire_state(): void
    {
        config([
            'muasamcong.smart_token' => 'server-only-token',
            'muasamcong.session_cookie' => 'server-only-cookie',
        ]);

        Livewire::test(ConfigManager::class)
            ->assertSet('form.smart_token', '')
            ->assertSet('form.session_cookie', '')
            ->assertSet('hasSmartToken', true)
            ->assertSet('hasSessionCookie', true)
            ->assertDontSee('server-only-token')
            ->assertDontSee('server-only-cookie');
    }

    public function test_config_ui_can_test_a_token_without_saving_it(): void
    {
        Http::fake([
            '*' => Http::response([
                'page' => [
                    'totalElements' => 2,
                    'content' => [],
                ],
            ]),
        ]);

        Livewire::test(ConfigManager::class)
            ->set('form.smart_token', 'temporary-test-token')
            ->call('testToken')
            ->assertSet('tokenTestStatus', 'success')
            ->assertSee('Token hợp lệ')
            ->assertSet('form.smart_token', 'temporary-test-token');
    }

    public function test_pricing_response_is_normalized_only_after_schema_validation(): void
    {
        Http::fake([
            '*' => Http::response([
                'page' => [
                    'totalElements' => 1,
                    'content' => [
                        ['tenThuoc' => 'Paracetamol'],
                        'invalid-row',
                    ],
                ],
            ]),
        ]);

        $result = app(MuaSamCongService::class)->searchPricing('paracetamol');

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['data']['total']);
        $this->assertCount(1, $result['data']['items']);
    }

    public function test_invalid_upstream_schema_returns_a_safe_error(): void
    {
        Http::fake(['*' => Http::response(['unexpected' => true])]);

        $result = app(MuaSamCongService::class)->searchPricing('paracetamol');

        $this->assertFalse($result['success']);
        $this->assertSame(502, $result['status']);
    }

    public function test_connection_exception_returns_a_safe_error(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $result = app(MuaSamCongService::class)->searchPricing('paracetamol');

        $this->assertFalse($result['success']);
        $this->assertSame(503, $result['status']);
        $this->assertStringNotContainsString('https://', $result['message']);
    }

    public function test_hsmt_without_smart_token_returns_a_safe_error(): void
    {
        config(['muasamcong.smart_token' => null]);

        $result = app(MuaSamCongService::class)
            ->searchHsmt('thuốc generic', '2026-07-01', '2026-07-31');

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('MUASAMCONG_SMART_TOKEN', $result['message']);
    }

    public function test_hsmt_export_creates_a_non_empty_xlsx_file(): void
    {
        $rows = [[
            'Tên gói thầu' => 'Gói thử',
            'Mã TBMT' => 'IBTEST',
            'Ngày đăng tải' => '2026-07-31',
            'Đóng thầu' => '2026-08-01',
            'Bên mời thầu' => 'Đơn vị thử',
            'Tỉnh' => 'Hà Nội',
        ]];

        $contents = Excel::raw(new HsmtExport($rows), ExcelFormat::XLSX);

        $this->assertGreaterThan(1000, strlen($contents));
        $this->assertStringStartsWith('PK', $contents);
    }
}
