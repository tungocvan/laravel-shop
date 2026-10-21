<?php

namespace Tests\Feature\Partner;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Partner\Services\BusinessRegistryLookupGateway;
use Tests\TestCase;

class BusinessRegistryLookupGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config()->set('services.doanhnghiep.api_key', null);
    }

    public function test_tax_code_uses_vietqr_and_caches_result(): void
    {
        Http::fake(['https://api.vietqr.io/v2/business/0316794479' => Http::response([
            'code' => '00', 'data' => ['id' => '0316794479', 'name' => 'CASSO', 'address' => 'TP HCM'],
        ])]);

        $gateway = app(BusinessRegistryLookupGateway::class);
        $this->assertSame('vietqr', $gateway->search('0316794479')[0]['source']);
        $this->assertSame('CASSO', $gateway->search('0316794479')[0]['name']);
        Http::assertSentCount(1);
    }

    public function test_name_uses_existing_mstcongty_provider_and_caches_result(): void
    {
        Http::fake(['https://mstcongty.com/tim-kiem*' => Http::response(
            '<a href="/1800553823-cong-ty-co-phan-benh-vien"><h3>Công ty Cổ phần Bệnh viện</h3>'.
            '<span>MST: 1800553823</span><span>Cần Thơ</span><span>Đang hoạt động</span></a>'
        )]);

        $gateway = app(BusinessRegistryLookupGateway::class);
        $this->assertSame('mstcongty', $gateway->search('Bệnh viện Hoàn Mỹ Cửu Long')[0]['source']);
        $this->assertSame('1800553823', $gateway->search('Bệnh viện Hoàn Mỹ Cửu Long')[0]['tax_code']);
        Http::assertSentCount(1);
    }

    public function test_name_provider_failure_does_not_cache_empty_results(): void
    {
        Http::fake(['https://mstcongty.com/tim-kiem*' => Http::response('Service unavailable', 503)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MSTCongTy trả về HTTP 503');

        app(BusinessRegistryLookupGateway::class)->search('Bệnh viện Cần Thơ');
    }

    public function test_vietqr_rejects_mismatched_tax_code(): void
    {
        Http::fake(['https://api.vietqr.io/v2/business/*' => Http::response([
            'code' => '00', 'data' => ['id' => '0100109106', 'name' => 'Khác'],
        ])]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('không khớp mã số thuế');

        app(BusinessRegistryLookupGateway::class)->search('0316794479');
    }
}
