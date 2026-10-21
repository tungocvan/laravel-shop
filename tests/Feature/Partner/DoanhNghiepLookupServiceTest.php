<?php

namespace Tests\Feature\Partner;

use Illuminate\Support\Facades\Http;
use Modules\Partner\Services\DoanhNghiepLookupService;
use Tests\TestCase;

class DoanhNghiepLookupServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.doanhnghiep.api_key', 'test-key');
    }

    public function test_search_maps_registry_candidates_without_writing_local_data(): void
    {
        Http::fake([
            'https://doanhnghiep.vn/api/v1/search*' => Http::response([
                'items' => [[
                    'mst' => '0314492345',
                    'name_vi' => 'Công Ty TNHH Inafo Việt Nam',
                    'legal_form' => 'Công ty TNHH Hai Thành Viên trở lên',
                    'status' => 'active',
                ]],
            ]),
        ]);

        $results = app(DoanhNghiepLookupService::class)->search('0314492345');

        $this->assertCount(1, $results);
        $this->assertSame('0314492345', $results[0]['tax_code']);
        $this->assertSame('Công Ty TNHH Inafo Việt Nam', $results[0]['name']);
        $this->assertSame('doanhnghiep_vn', $results[0]['source']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/v1/search') && $request->hasHeader('x-api-key', 'test-key'));
    }

    public function test_detail_maps_partner_review_fields(): void
    {
        Http::fake([
            'https://doanhnghiep.vn/api/v1/companies/0314492345' => Http::response([
                'mst' => '0314492345',
                'name_vi' => 'Công Ty TNHH Inafo Việt Nam',
                'legal_form' => 'Công ty TNHH Hai Thành Viên trở lên',
                'registered_at' => '2017-07-04',
                'status' => 'active',
                'address_full' => '240/127/26 Nguyễn Văn Luông, TP. Hồ Chí Minh',
                'legal_rep_name' => 'Phạm Hồng Thái',
                'province' => [
                    'code' => 'VN-HCM',
                    'name_vi' => 'TP. Hồ Chí Minh',
                ],
            ]),
        ]);

        $detail = app(DoanhNghiepLookupService::class)->fetchDetail('0314492345');

        $this->assertSame('240/127/26 Nguyễn Văn Luông, TP. Hồ Chí Minh', $detail['tax_address']);
        $this->assertSame('Phạm Hồng Thái', $detail['representative']);
        $this->assertSame('2017-07-04', $detail['active_since']);
        $this->assertSame('VN-HCM', $detail['province_code']);
        $this->assertSame('Công ty TNHH Hai Thành Viên trở lên', $detail['organization_type']);
    }

    public function test_rate_limit_is_exposed_as_a_controlled_failure(): void
    {
        Http::fake([
            'https://doanhnghiep.vn/api/v1/search*' => Http::response([], 429),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('giới hạn tần suất');

        app(DoanhNghiepLookupService::class)->search('INAFO');
    }
    public function test_missing_key_stops_before_http_request(): void
    {
        config()->set('services.doanhnghiep.api_key', null);
        Http::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Chưa cấu hình API key');

        try {
            app(DoanhNghiepLookupService::class)->search('Bệnh viện');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_unauthorized_key_is_explained(): void
    {
        Http::fake(['https://doanhnghiep.vn/api/v1/search*' => Http::response([], 401)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API key doanhnghiep.vn không hợp lệ');

        app(DoanhNghiepLookupService::class)->search('Bệnh viện');
    }
}
