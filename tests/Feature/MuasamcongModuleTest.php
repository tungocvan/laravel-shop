<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Artisan;
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

    public function test_config_mount_is_read_only_and_does_not_clear_config_cache(): void
    {
        Artisan::spy();

        Livewire::test(ConfigManager::class)
            ->assertSet('form.smart_token', '')
            ->assertSet('form.session_cookie', '');

        Artisan::shouldNotHaveReceived('call');
    }

    public function test_config_token_test_requires_privileged_admin_capability(): void
    {
        Livewire::test(ConfigManager::class)
            ->set('form.smart_token', 'temporary-test-token')
            ->call('testToken')
            ->assertForbidden();
    }

    public function test_service_can_test_temporary_token_without_persisting_it(): void
    {
        Http::fake([
            '*' => Http::response([
                'page' => [
                    'totalElements' => 2,
                    'content' => [],
                ],
            ]),
        ]);

        $result = app(MuaSamCongService::class)->testSmartToken(
            'temporary-test-token',
            'session=value'
        );

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['data']['total']);

        Http::assertSent(function (ClientRequest $request): bool {
            return str_contains($request->url(), 'token=temporary-test-token')
                && $request->hasHeader('Cookie', 'session=value');
        });
    }

    public function test_unapproved_upstream_host_is_rejected_before_any_http_request(): void
    {
        config([
            'muasamcong.endpoints.contractor_search' => 'https://127.0.0.1/internal',
        ]);

        Http::fake();

        $result = app(MuaSamCongService::class)->testSmartToken(
            'secret-token',
            'secret-cookie'
        );

        $this->assertFalse($result['success']);
        $this->assertSame(500, $result['status']);
        Http::assertNothingSent();
    }

    public function test_http_upstream_url_is_rejected_even_on_the_approved_host(): void
    {
        config([
            'muasamcong.endpoints.pricing' => 'http://muasamcong.mpi.gov.vn/internal',
        ]);

        Http::fake();

        $result = app(MuaSamCongService::class)->searchPricing('paracetamol');

        $this->assertFalse($result['success']);
        $this->assertSame(500, $result['status']);
        Http::assertNothingSent();
    }

    public function test_pricing_search_includes_winning_name_and_returns_bidder_results_without_medicine_fallback(): void
    {
        Http::fake([
            '*' => Http::response([
                'page' => [
                    'totalElements' => 1,
                    'content' => [[
                        'id' => '33333333-3333-4333-8333-333333333333',
                        'tenThuoc' => 'Pidoncam',
                        'winningName' => ['CÔNG TY CỔ PHẦN DƯỢC PHẨM NAM SƠN - NAMPHACO'],
                    ]],
                ],
            ]),
        ]);

        $result = app(MuaSamCongService::class)->searchPricing('NAM SƠN');

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['data']['total']);
        $this->assertSame('Pidoncam', $result['data']['items'][0]['tenThuoc']);

        Http::assertSentCount(1);
        Http::assertSent(function (ClientRequest $request): bool {
            $query = $request->data()[0]['query'][0] ?? [];

            return ($query['keyWord'] ?? null) === 'NAM SƠN'
                && ($query['matchType'] ?? null) === 'exact'
                && ($query['matchFields'] ?? null) === [
                    'ten_thuoc',
                    'ten_hoat_chat',
                    'ma_tbmt',
                    'winning_name',
                ];
        });
    }

    public function test_pricing_search_falls_back_for_punctuation_and_keeps_the_exact_normalized_medicine_name(): void
    {
        Http::fakeSequence()
            ->push(['page' => ['totalElements' => 0, 'content' => []]])
            ->push(['page' => [
                'totalElements' => 2,
                'content' => [
                    ['id' => '11111111-1111-4111-8111-111111111111', 'tenThuoc' => 'Gourcuff-5'],
                    ['id' => '22222222-2222-4222-8222-222222222222', 'tenThuoc' => 'Gourcuff-2.5'],
                ],
            ]]);

        $result = app(MuaSamCongService::class)->searchPricing('Gourcuff-2,5');

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['data']['total']);
        $this->assertSame('Gourcuff-2.5', $result['data']['items'][0]['tenThuoc']);
        Http::assertSentCount(2);
    }

    public function test_pricing_search_uses_base_name_when_dosage_suffix_queries_are_empty(): void
    {
        Http::fakeSequence()
            ->push(['page' => ['totalElements' => 0, 'content' => []]])
            ->push(['page' => ['totalElements' => 0, 'content' => []]])
            ->push(['page' => [
                'totalElements' => 2,
                'content' => [
                    ['id' => '11111111-1111-4111-8111-111111111111', 'tenThuoc' => 'Gourcuff-5'],
                    ['id' => '22222222-2222-4222-8222-222222222222', 'tenThuoc' => 'Gourcuff-2.5'],
                ],
            ]]);

        $result = app(MuaSamCongService::class)->searchPricing('Gourcuff-2,5');

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['data']['total']);
        $this->assertSame('Gourcuff-2.5', $result['data']['items'][0]['tenThuoc']);

        $keywords = [];
        Http::assertSent(function (ClientRequest $request) use (&$keywords): bool {
            $payload = $request->data();
            $keywords[] = $payload[0]['query'][0]['keyWord'] ?? null;

            return true;
        });

        $this->assertSame(['Gourcuff-2,5', 'Gourcuff-2.5', 'Gourcuff'], $keywords);
    }

    public function test_pricing_search_recovers_when_decimal_comma_request_gets_http_400(): void
    {
        Http::fakeSequence()
            ->push(['message' => 'Bad Request'], 400)
            ->push(['page' => [
                'totalElements' => 2,
                'content' => [
                    ['id' => '11111111-1111-4111-8111-111111111111', 'tenThuoc' => 'Gourcuff-5'],
                    ['id' => '22222222-2222-4222-8222-222222222222', 'tenThuoc' => 'Gourcuff-2,5'],
                ],
            ]]);

        $result = app(MuaSamCongService::class)->searchPricing('Gourcuff-2,5');

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['data']['total']);
        $this->assertSame('Gourcuff-2,5', $result['data']['items'][0]['tenThuoc']);
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
