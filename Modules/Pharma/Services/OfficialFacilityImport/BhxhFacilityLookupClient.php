<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BhxhFacilityLookupClient
{
    public const CAPTCHA_URL = 'https://baohiemxahoi.gov.vn/UserControls/CaptchaImageHandler.ashx';

    public const LOOKUP_URL = 'https://baohiemxahoi.gov.vn/UserControls/Publishing/TraCuuCoSoKCB/pListCSKCBDangKy.aspx';

    public const REFERER_URL = 'https://baohiemxahoi.gov.vn/tracuu/Pages/cskcb-ky-hop-dong-kham-chua-benh-bhyt.aspx';

    public function captcha(): array
    {
        try {
            $response = Http::timeout(15)
                ->accept('*/*')
                ->withHeaders($this->browserHeaders())
                ->get(self::CAPTCHA_URL);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kết nối cổng BHXH để tải CAPTCHA.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Cổng BHXH trả lỗi khi tải CAPTCHA (HTTP '.$response->status().').');
        }

        $cookies = $this->extractCookies($response->headers()['Set-Cookie'] ?? []);

        return [
            'body' => $response->body(),
            'content_type' => $response->header('Content-Type') ?: 'image/png',
            'cookies' => $cookies,
        ];
    }

    public function lookup(string $provinceCode, ?string $districtCode, string $captcha, array $cookies): array
    {
        if ($cookies === []) {
            throw new RuntimeException('Phiên CAPTCHA BHXH chưa tồn tại hoặc đã hết hạn. Hãy tải CAPTCHA mới.');
        }

        try {
            $response = Http::timeout(20)
                ->asForm()
                ->withHeaders(array_merge($this->browserHeaders(), [
                    'Accept' => 'text/html, */*; q=0.01',
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Cookie' => $this->cookieHeader($cookies),
                    'Origin' => 'https://baohiemxahoi.gov.vn',
                    'X-Requested-With' => 'XMLHttpRequest',
                ]))
                ->post(self::LOOKUP_URL, [
                    'MaTinh' => $provinceCode,
                    'MaQuanHuyen' => $districtCode ?? '',
                    'tokenRecaptch' => $captcha,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kết nối cổng BHXH để tra cứu cơ sở KCB.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Cổng BHXH trả lỗi khi tra cứu cơ sở KCB (HTTP '.$response->status().').');
        }

        return [
            'facilities' => $this->parseFacilities($response->body()),
            'message' => $this->extractMessage($response->body()),
        ];
    }

    public function parseFacilities(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $facilities = [];

        foreach ($xpath->query('//tr') ?: [] as $row) {
            $cells = $xpath->query('./td', $row);
            if ($cells === false || $cells->length < 3) {
                continue;
            }

            $externalId = $this->cleanText($cells->item(1)?->textContent);
            $name = $this->cleanText($cells->item(2)?->textContent);

            if ($externalId === '' || $name === '') {
                continue;
            }

            $facilities[] = [
                'external_id' => $externalId,
                'facility_name' => $name,
            ];
        }

        return $facilities;
    }

    private function extractMessage(string $html): ?string
    {
        $text = $this->cleanText(strip_tags($html));

        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, 500);
    }

    private function browserHeaders(): array
    {
        return [
            'Referer' => self::REFERER_URL,
            'User-Agent' => 'Mozilla/5.0 (compatible; Laravel ERP Official Facility Lookup)',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ];
    }

    private function extractCookies(array|string $setCookieHeaders): array
    {
        $headers = is_array($setCookieHeaders) ? $setCookieHeaders : [$setCookieHeaders];
        $cookies = [];

        foreach ($headers as $header) {
            $pair = trim(strtok($header, ';') ?: '');
            if ($pair === '' || ! str_contains($pair, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $pair, 2);
            $name = trim($name);
            if ($name !== '') {
                $cookies[$name] = trim($value);
            }
        }

        return $cookies;
    }

    private function cookieHeader(array $cookies): string
    {
        return collect($cookies)
            ->map(fn ($value, $name) => $name.'='.$value)
            ->implode('; ');
    }

    private function cleanText(?string $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value;
    }
}
