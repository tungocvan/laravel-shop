<?php

namespace Modules\Pharma\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class DavDomesticGmpCrawler
{
    private const HOST = 'dav.gov.vn';
    private const CATEGORY = 'https://dav.gov.vn/danh-sach-cssx-duoc-cap-gmp-cua-dav-cn92.html';
    private const ARTICLE_PATTERN = '~^/danh-sach-cac-cssx-trong-nuoc-dat-tieu-chuan-nguyen-tac-gmp-gmp-bao-bi-duoc-pham-cap-nhat-toi-ngay-[a-z0-9-]+-n[0-9]+\.html$~i';

    public function latest(): array
    {
        $html = $this->getHtml(self::CATEGORY);
        $links = $this->links($html);
        $candidates = [];
        foreach ($links as $link) {
            $path = parse_url($link['url'], PHP_URL_PATH);
            if (! is_string($path) || ! preg_match(self::ARTICLE_PATTERN, $path)) {
                continue;
            }
            if (! preg_match('~-n(\d+)\.html$~', $path, $match)) {
                continue;
            }
            $candidates[$link['url']] = (int) $match[1];
        }
        if ($candidates === []) {
            throw new RuntimeException('DAV GMP category did not expose any matching publication.');
        }
        arsort($candidates, SORT_NUMERIC);
        // The news ID is only a discovery ordering hint; the article itself
        // supplies the actual publication and data-update dates.
        $url = (string) array_key_first($candidates);

        return $this->article($url);
    }

    public function article(string $url): array
    {
        $this->assertArticleUrl($url);
        $html = $this->getHtml($url);
        $dom = $this->dom($html);
        $xpath = new DOMXPath($dom);
        $title = trim((string) $xpath->evaluate('string(//div[@id="DivIdToPrint"]//h1[contains(@class,"news-title")])'));
        $published = trim((string) $xpath->evaluate('string(//div[@id="DivIdToPrint"]//time)'));
        if (! str_contains(mb_strtolower($title), 'gmp') || ! str_contains(mb_strtolower($title), 'trong nước')) {
            throw new RuntimeException('DAV article does not match domestic GMP publication.');
        }

        // DAV's actual n5553 HTML exposes attachment links in the page, but
        // not necessarily as descendants of the print container. Search the
        // document and classify only official, named Excel attachments.
        $files = [];
        $seen = [];
        foreach ($xpath->query('//a[@href]') as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            $path = parse_url($href, PHP_URL_PATH);
            if (! is_string($path) || ! preg_match('~^/upload_images/files/[^?#]+\.xlsx$~i', $path)) {
                continue;
            }
            try {
                $fileUrl = $this->absoluteUrl($href);
                $this->assertFileUrl($fileUrl);
            } catch (RuntimeException) {
                continue;
            }
            if (isset($seen[$fileUrl])) {
                continue;
            }
            $seen[$fileUrl] = true;
            $name = basename(rawurldecode($path));
            $normalized = mb_strtolower($name, 'UTF-8');
            if (str_contains($normalized, 'chứng nhận đủ điều kiện kinh doanh dược')) {
                $group = 'business_certificates';
            } elseif (str_contains($normalized, 'gmp bao bì')) {
                $group = 'packaging';
            } elseif (str_contains($normalized, 'trong nước') && str_contains($normalized, 'gmp')) {
                $group = 'manufacturers';
            } else {
                continue;
            }
            if (isset($files[$group])) {
                throw new RuntimeException('DAV article has ambiguous Excel attachments for '.$group);
            }
            $files[$group] = [
                'group' => $group,
                'url' => $fileUrl,
                'name' => $name,
            ];
        }
        if (count($files) !== 3) {
            throw new RuntimeException('DAV article has '.count($files).' of the three expected classified Excel groups.');
        }

        return ['url' => $url, 'title' => $title, 'published' => $published, 'files' => $files];
    }

    public function download(string $url): string
    {
        $this->assertFileUrl($url);
        $response = Http::timeout(35)->connectTimeout(10)->withOptions(['allow_redirects' => false])
            ->withHeaders(['Accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream'])
            ->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('DAV Excel download failed with HTTP '.$response->status());
        }
        $body = $response->body();
        if (strlen($body) < 4 || strlen($body) > 20 * 1024 * 1024 || ! str_starts_with($body, "PK\x03\x04")) {
            throw new RuntimeException('DAV attachment is not a valid-sized XLSX ZIP payload.');
        }

        return $body;
    }

    private function getHtml(string $url): string
    {
        $response = Http::timeout(20)->connectTimeout(8)->withOptions(['allow_redirects' => false])
            ->withHeaders(['Accept' => 'text/html'])->get($url);
        if (! $response->successful() || ! str_contains(strtolower($response->header('Content-Type', '')), 'text/html')) {
            throw new RuntimeException('DAV did not return a valid HTML publication.');
        }
        $body = $response->body();
        if (strlen($body) > 3 * 1024 * 1024) {
            throw new RuntimeException('DAV HTML exceeds safe size limit.');
        }

        return $body;
    }

    private function links(string $html): array
    {
        $links = [];
        foreach ((new DOMXPath($this->dom($html)))->query('//a[@href]') as $anchor) {
            try {
                $links[] = ['url' => $this->absoluteUrl($anchor->getAttribute('href'))];
            } catch (RuntimeException) {
                continue;
            }
        }

        return $links;
    }

    private function absoluteUrl(string $href): string
    {
        if (str_starts_with($href, '/')) {
            return 'https://'.self::HOST.$href;
        }
        if (str_starts_with($href, 'https://'.self::HOST.'/')) {
            return $href;
        }
        throw new RuntimeException('Untrusted DAV URL.');
    }

    private function assertArticleUrl(string $url): void
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== self::HOST
            || ! is_string($path) || ! preg_match(self::ARTICLE_PATTERN, $path)
            || parse_url($url, PHP_URL_QUERY) !== null || parse_url($url, PHP_URL_FRAGMENT) !== null) {
            throw new RuntimeException('Untrusted DAV article URL.');
        }
    }

    private function assertFileUrl(string $url): void
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== self::HOST
            || ! is_string($path) || ! preg_match('~^/upload_images/files/[^?#]+\.xlsx$~i', $path)
            || parse_url($url, PHP_URL_QUERY) !== null || parse_url($url, PHP_URL_FRAGMENT) !== null) {
            throw new RuntimeException('Untrusted DAV Excel URL.');
        }
    }

    private function dom(string $html): DOMDocument
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $dom;
    }
}
