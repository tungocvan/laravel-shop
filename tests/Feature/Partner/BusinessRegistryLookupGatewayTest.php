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

    public function test_name_requires_configured_provider_and_never_calls_vietqr(): void
    {
        Http::fake();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tra cứu theo tên cần API key');

        try {
            app(BusinessRegistryLookupGateway::class)->search('Bệnh viện Cần Thơ');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_name_uses_authenticated_doanhnghiep_and_cache(): void
    {
        config()->set('services.doanhnghiep.api_key', 'fake-test-key');
        Http::fake(['https://doanhnghiep.vn/api/v1/search*' => Http::response([
            'items' => [['mst' => '0314492345', 'name_vi' => 'Công ty mẫu']],
        ])]);

        $gateway = app(BusinessRegistryLookupGateway::class);
        $this->assertSame('doanhnghiep_vn', $gateway->search('Công ty mẫu')[0]['source']);
        $this->assertCount(1, $gateway->search('Công ty mẫu'));
        Http::assertSentCount(1);
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
