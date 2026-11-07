<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\Pharma\Services\DavDomesticGmpCrawler;
use Modules\Pharma\Services\DavDomesticGmpSnapshot;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class PharmaDomesticGmpOnDemandTest extends TestCase
{
    private const ARTICLE = 'https://dav.gov.vn/danh-sach-cac-cssx-trong-nuoc-dat-tieu-chuan-nguyen-tac-gmp-gmp-bao-bi-duoc-pham-cap-nhat-toi-ngay-23102026-n5553.html';

    private string $davMarker = 'first';
    private bool $davBadPackaging = false;
    private bool $davFakeRegistered = false;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_initial_refresh_stores_three_files_and_repeat_is_unchanged(): void
    {
        $this->fakeDav('first');
        $service = app(DavDomesticGmpSnapshot::class);
        $this->assertNull($service->current());

        $first = $service->refresh(app(DavDomesticGmpCrawler::class));
        $this->assertSame('updated', $first['status']);
        $this->assertCount(3, $first['snapshot']['files']);
        $this->assertSame('unchanged', $service->refresh(app(DavDomesticGmpCrawler::class))['status']);

        $snapshot = $service->current();
        $this->assertSame($first['snapshot']['version'], $snapshot['version']);
        foreach (['manufacturers', 'packaging', 'business_certificates'] as $group) {
            Storage::disk('local')->assertExists($service->filePath($snapshot['version'], $group));
        }
    }

    public function test_failed_second_download_preserves_existing_snapshot(): void
    {
        $this->fakeDav('first');
        $service = app(DavDomesticGmpSnapshot::class);
        $first = $service->refresh(app(DavDomesticGmpCrawler::class))['snapshot'];
        $this->fakeDav('second', true);

        try {
            $service->refresh(app(DavDomesticGmpCrawler::class));
            $this->fail('Expected invalid DAV workbook to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ZIP', $exception->getMessage());
        }

        $this->assertSame($first['version'], $service->current()['version']);
        Storage::disk('local')->assertExists($service->filePath($first['version'], 'manufacturers'));
    }

    public function test_changed_excel_replaces_previous_managed_version_only_after_validation(): void
    {
        $service = app(DavDomesticGmpSnapshot::class);
        $this->fakeDav('first');
        $first = $service->refresh(app(DavDomesticGmpCrawler::class))['snapshot'];
        $this->fakeDav('second');
        $second = $service->refresh(app(DavDomesticGmpCrawler::class))['snapshot'];
        $this->assertNotSame($first['version'], $second['version']);
        $this->assertFalse(Storage::disk('local')->exists($service->filePath($first['version'], 'manufacturers')));
        Storage::disk('local')->assertExists($service->filePath($second['version'], 'manufacturers'));
    }

    public function test_refresh_route_requires_post_and_feature_authorization(): void
    {
        $route = Route::getRoutes()->getByName('client.pharma.domestic-gmp.refresh');
        $this->assertNotNull($route);
        $this->assertSame(['POST'], $route->methods());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.feature:pharma,domestic-gmp', $route->gatherMiddleware());

        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaDomesticGmpController.php'));
        $this->assertStringContainsString('$snapshots->current()', $controller);
        $this->assertStringContainsString('$snapshots->refresh($crawler)', $controller);
        $this->assertStringNotContainsString('$crawler->latest();', $controller);
    }

    private function fakeDav(string $marker, bool $badPackaging = false): void
    {
        // Http::fake() callbacks are cumulative in Laravel; registering a
        // second fake does not override the first matching callback.
        // Keep one callback and change the response state between refreshes.
        $this->davMarker = $marker;
        $this->davBadPackaging = $badPackaging;
        if ($this->davFakeRegistered) {
            return;
        }
        $this->davFakeRegistered = true;

        $article = self::ARTICLE;
        $category = 'https://dav.gov.vn/danh-sach-cssx-duoc-cap-gmp-cua-dav-cn92.html';
        $files = [
            'Danh%20s%C3%A1ch%20c%C3%A1c%20c%C3%B4ng%20ty%20trong%20n%C6%B0%E1%BB%9Bc%20GMP.xlsx',
            'Danh%20s%C3%A1ch%20c%C3%A1c%20c%C3%B4ng%20ty%20trong%20n%C6%B0%E1%BB%9Bc%20GMP%20bao%20b%C3%AC.xlsx',
            'Danh%20s%C3%A1ch%20ch%E1%BB%A9ng%20nh%E1%BA%ADn%20%C4%91%E1%BB%A7%20%C4%91i%E1%BB%81u%20ki%E1%BB%87n%20kinh%20doanh%20d%C6%B0%E1%BB%A3c.xlsx',
        ];
        $html = '<div id="DivIdToPrint"><h1 class="news-title">Danh sách CSSX trong nước đạt GMP</h1><time>28/10/2026</time></div>';
        foreach ($files as $file) {
            $html .= '<a href="/upload_images/files/'.$file.'">Excel</a>';
        }

        Http::preventStrayRequests();
        Http::fake(function ($request) use ($category, $article, $html, $files) {
            $url = $request->url();
            if ($url === $category) {
                return Http::response('<a href="'.$article.'">Latest</a>', 200, ['Content-Type' => 'text/html']);
            }
            if ($url === $article) {
                return Http::response($html, 200, ['Content-Type' => 'text/html']);
            }
            foreach ($files as $index => $file) {
                if ($url === 'https://dav.gov.vn/upload_images/files/'.$file) {
                    return Http::response(
                        $this->davBadPackaging && $index === 1
                            ? 'not a ZIP'
                            : $this->xlsx($this->davMarker.'-'.$index),
                        200,
                        ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                    );
                }
            }
            throw new RuntimeException('Unexpected DAV request: '.$url);
        });
    }

    private function xlsx(string $marker): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gmp-test-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<Types>'.$marker.'</Types>');
        $zip->addFromString('xl/workbook.xml', '<workbook>'.$marker.'</workbook>');
        $zip->close();
        $bytes = file_get_contents($path);
        unlink($path);

        return $bytes;
    }
}
