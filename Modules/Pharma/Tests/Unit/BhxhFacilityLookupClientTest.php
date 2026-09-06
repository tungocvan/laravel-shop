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
            BhxhFacilityLookupClient::REFERER_URL => Http::response('<html></html>', 200, [
                'Set-Cookie' => 'ASP.NET_SessionId=session-123; path=/; HttpOnly',
            ]),
            BhxhFacilityLookupClient::CAPTCHA_URL => Http::response('image-bytes', 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $result = app(BhxhFacilityLookupClient::class)->captcha();

        $this->assertSame('image-bytes', $result['body']);
        $this->assertSame('image/png', $result['content_type']);
        $this->assertSame(['ASP.NET_SessionId' => 'session-123'], $result['cookies']);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== BhxhFacilityLookupClient::CAPTCHA_URL) {
                return true;
            }

            return str_contains($request->header('Cookie')[0] ?? '', 'ASP.NET_SessionId=session-123');
        });
    }

    #[Test]
    public function lookup_posts_exact_bhxh_form_body_and_parses_facilities(): void
    {
        Http::fake([
            BhxhFacilityLookupClient::LOOKUP_URL => Http::response(<<<'HTML'
                <table>
                    <tr><th>STT</th><th>Mã CSKCB</th><th>Tên CSKCB</th></tr>
                    <tr><td>1</td><td>93108</td><td>Trung tâm Y tế thành phố Ngã Bảy</td></tr>
                    <tr><td>2</td><td>94170</td><td>Bệnh viện Quốc tế Phương Châu Sóc Trăng</td></tr>
                </table>
                HTML),
        ]);

        $result = app(BhxhFacilityLookupClient::class)->lookup(
            '92TTT',
            null,
            'EXQH7',
            ['ASP.NET_SessionId' => 'session-123'],
        );

        Http::assertSent(function (Request $request): bool {
            return $request->url() === BhxhFacilityLookupClient::LOOKUP_URL
                && $request->body() === 'MaTinh=92TTT&MaQuanHuyen=&tokenRecaptch=EXQH7'
                && str_contains($request->header('Content-Type')[0] ?? '', 'application/x-www-form-urlencoded')
                && str_contains($request->header('Cookie')[0] ?? '', 'ASP.NET_SessionId=session-123')
                && ($request->header('X-Requested-With')[0] ?? '') === 'XMLHttpRequest';
        });

        $this->assertCount(2, $result['facilities']);
        $this->assertSame('93108', $result['facilities'][0]['external_id']);
        $this->assertSame('Trung tâm Y tế thành phố Ngã Bảy', $result['facilities'][0]['facility_name']);
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
