<?php

namespace Modules\Pharma\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Pharma\Services\OfficialFacilityImport\BhxhFacilityLookupClient;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BhxhFacilityLookupClientTest extends TestCase
{
    #[Test]
    public function captcha_bootstraps_page_session_then_reuses_cookie_for_image(): void
    {
        Http::fake([
            BhxhFacilityLookupClient::REFERER_URL => Http::response('<html></html>', 200, ['Set-Cookie' => 'ASP.NET_SessionId=session-123; path=/; HttpOnly']),
            BhxhFacilityLookupClient::CAPTCHA_URL => Http::response('image-bytes', 200, ['Content-Type' => 'image/png']),
        ]);
        $result = app(BhxhFacilityLookupClient::class)->captcha();
        $this->assertSame('image-bytes', $result['body']);
        $this->assertSame(['ASP.NET_SessionId' => 'session-123'], $result['cookies']);
        $captchaRequests = Http::recorded(fn (Request $request) => $request->url() === BhxhFacilityLookupClient::CAPTCHA_URL);
        $this->assertCount(1, $captchaRequests);
        $this->assertStringContainsString('ASP.NET_SessionId=session-123', $captchaRequests[0][0]->header('Cookie')[0] ?? '');
    }

    #[Test]
    public function districts_post_bhxh_json_contract_and_parse_aspnet_d_payload(): void
    {
        Http::fake([BhxhFacilityLookupClient::DISTRICT_URL => Http::response(['d' => json_encode([
            ['MAHUYEN' => '916', 'TENHUYEN' => 'Quận Ninh Kiều'], ['MAHUYEN' => '917', 'TENHUYEN' => 'Quận Ô Môn'],
        ], JSON_UNESCAPED_UNICODE)])]);
        $districts = app(BhxhFacilityLookupClient::class)->districts('92TTT');
        Http::assertSent(fn (Request $request): bool => $request->url() === BhxhFacilityLookupClient::DISTRICT_URL && $request['lstmatinh'] === '92TTT' && str_contains($request->header('Content-Type')[0] ?? '', 'application/json'));
        $this->assertSame([['code' => '916', 'name' => 'Quận Ninh Kiều'], ['code' => '917', 'name' => 'Quận Ô Môn']], $districts);
    }

    #[Test]
    public function lookup_posts_exact_form_and_reports_all_response_columns_without_exposing_values(): void
    {
        Http::fake([BhxhFacilityLookupClient::LOOKUP_URL => Http::response(<<<'HTML'
            <table data-source="bhxh">
                <tr><th>STT</th><th>Mã CSKCB</th><th>Tên CSKCB</th><th>Địa chỉ</th></tr>
                <tr data-row-id="1"><td>1</td><td>93108</td><td>Trung tâm Y tế thành phố Ngã Bảy</td><td>Địa chỉ thử nghiệm</td></tr>
                <tr><td>2</td><td>94170</td><td>Bệnh viện Quốc tế Phương Châu Sóc Trăng</td><td>Địa chỉ 2</td></tr>
            </table>
            <input type="hidden" name="pageIndex" value="secret-value">
            HTML)]);
        $result = app(BhxhFacilityLookupClient::class)->lookup('92TTT', null, 'EXQH7', ['ASP.NET_SessionId' => 'session-123']);
        Http::assertSent(fn (Request $request): bool => $request->url() === BhxhFacilityLookupClient::LOOKUP_URL
            && $request->body() === 'MaTinh=92TTT&MaQuanHuyen=&tokenRecaptch=EXQH7'
            && str_contains($request->header('Cookie')[0] ?? '', 'ASP.NET_SessionId=session-123'));
        $this->assertCount(2, $result['facilities']);
        $this->assertSame('93108', $result['facilities'][0]['external_id']);
        $this->assertSame(['STT', 'Mã CSKCB', 'Tên CSKCB', 'Địa chỉ'], $result['structure']['headers']);
        $this->assertSame([4 => 2], $result['structure']['column_counts']);
        $this->assertSame(['pageIndex'], $result['structure']['hidden_fields']);
        $this->assertContains('data-source', $result['structure']['data_attributes']);
        $this->assertContains('data-row-id', $result['structure']['data_attributes']);
        $this->assertStringNotContainsString('secret-value', json_encode($result['structure']));
    }

    #[Test]
    public function client_does_not_attempt_to_solve_captcha(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Services/OfficialFacilityImport/BhxhFacilityLookupClient.php'));
        $this->assertStringContainsString("'tokenRecaptch' => \$captcha", $source);
        $this->assertStringNotContainsString('OCR', $source);
        $this->assertStringNotContainsString('tesseract', strtolower($source));
    }
}
