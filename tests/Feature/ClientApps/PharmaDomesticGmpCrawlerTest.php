<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\DavDomesticGmpCrawler;
use RuntimeException;
use Tests\TestCase;

class PharmaDomesticGmpCrawlerTest extends TestCase
{
    private const ARTICLE = 'https://dav.gov.vn/danh-sach-cac-cssx-trong-nuoc-dat-tieu-chuan-nguyen-tac-gmp-gmp-bao-bi-duoc-pham-cap-nhat-toi-ngay-23102026-n5553.html';
    private const CATEGORY = 'https://dav.gov.vn/danh-sach-cssx-duoc-cap-gmp-cua-dav-cn92.html';

    public function test_discovers_publication_and_classifies_three_excel_groups(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            self::CATEGORY => Http::response('<html><a href="'.self::ARTICLE.'">GMP mới</a><a href="/danh-sach-cac-cssx-trong-nuoc-dat-tieu-chuan-nguyen-tac-gmp-gmp-bao-bi-duoc-pham-cap-nhat-toi-ngay-2662026-n5423.html">Cũ</a></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']),
            self::ARTICLE => Http::response($this->articleHtml(), 200, ['Content-Type' => 'text/html; charset=UTF-8']),
        ]);
        $result = app(DavDomesticGmpCrawler::class)->latest();
        $this->assertSame(self::ARTICLE, $result['url']);
        $this->assertStringContainsString('23/10/2026', $result['title']);
        $this->assertCount(3, $result['files']);
        $this->assertSame('Danh sách các công ty trong nước đạt tiêu chuẩn GMP (23_10_2026).xlsx', $result['files']['manufacturers']['name']);
        $this->assertSame('Danh sách các công ty trong nước đạt tiêu chuẩn GMP bao bì (23_10_2026).xlsx', $result['files']['packaging']['name']);
        $this->assertSame('Danh sách chứng nhận đủ điều kiện kinh doanh dược (23_10_2026).xlsx', $result['files']['business_certificates']['name']);
        Http::assertSentCount(2);
    }

    public function test_rejects_untrusted_file_url_and_html_disguised_as_excel(): void
    {
        $crawler = app(DavDomesticGmpCrawler::class);
        $this->expectException(RuntimeException::class);
        $crawler->download('https://attacker.example/upload_images/files/fake.xlsx');
    }

    public function test_rejects_invalid_excel_response(): void
    {
        Http::preventStrayRequests();
        Http::fake(['dav.gov.vn/*' => Http::response('<html>error</html>', 200, ['Content-Type' => 'text/html'])]);
        $this->expectException(RuntimeException::class);
        app(DavDomesticGmpCrawler::class)->download('https://dav.gov.vn/upload_images/files/GMP.xlsx');
    }

    public function test_routes_and_manifest_are_guarded_and_read_only(): void
    {
        $index = Route::getRoutes()->getByName('client.pharma.domestic-gmp');
        $download = Route::getRoutes()->getByName('client.pharma.domestic-gmp.download');
        $this->assertNotNull($index);
        $this->assertNotNull($download);
        $this->assertSame('apps/pharma/domestic-gmp', $index->uri());
        $this->assertContains('auth:web', $index->gatherMiddleware());
        $this->assertContains('client.feature:pharma,domestic-gmp', $index->gatherMiddleware());
        $this->assertContains('client.feature:pharma,domestic-gmp', $download->gatherMiddleware());
        $this->assertSame(['GET', 'HEAD'], $download->methods());
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $this->assertSame('client.pharma.dav-prices.view', $manifest['features']['domestic-gmp']['permission']);
    }

    private function articleHtml(): string
    {
        return <<<'HTML'
<html><div id="DivIdToPrint">
<h1 class="news-title">Danh sách CSSX trong nước đạt GMP (23/10/2026)</h1>
<time>28/10/2026</time>
<div class="description row-item">
<p>1. Danh sách CSSX trong nước đạt GMP</p>
<table><tbody><tr><td><div class="nested-attachments">
<p><a href="/upload_images/files/GMP-thuoc.pdf">PDF</a></p>
<p><a href="/upload_images/files/Danh%20s%C3%A1ch%20c%C3%A1c%20c%C3%B4ng%20ty%20trong%20n%C6%B0%E1%BB%9Bc%20%C4%91%E1%BA%A1t%20ti%C3%AAu%20chu%E1%BA%A9n%20GMP%20(23_10_2026).xlsx">Excel</a></p>
<p>2. Danh sách CSSX trong nước đạt GMP bao bì</p>
<p><a href="/upload_images/files/Danh%20s%C3%A1ch%20c%C3%A1c%20c%C3%B4ng%20ty%20trong%20n%C6%B0%E1%BB%9Bc%20%C4%91%E1%BA%A1t%20ti%C3%AAu%20chu%E1%BA%A9n%20GMP%20bao%20b%C3%AC%20(23_10_2026).xlsx">Excel</a></p>
<p>3. Giấy chứng nhận đủ điều kiện kinh doanh dược</p>
<p><a href="/upload_images/files/Danh%20s%C3%A1ch%20ch%E1%BB%A9ng%20nh%E1%BA%ADn%20%C4%91%E1%BB%A7%20%C4%91i%E1%BB%81u%20ki%E1%BB%87n%20kinh%20doanh%20d%C6%B0%E1%BB%A3c%20(23_10_2026).xlsx">Excel</a></p>
</div></td></tr></tbody></table>
</div></div><div class="attachment-outside-print"><a href="/upload_images/files/Danh%20s%C3%A1ch%20c%C3%A1c%20c%C3%B4ng%20ty%20trong%20n%C6%B0%E1%BB%9Bc%20%C4%91%E1%BA%A1t%20ti%C3%AAu%20chu%E1%BA%A9n%20GMP%20(23_10_2026).xlsx">duplicate</a></div></html>
HTML;
    }
}
