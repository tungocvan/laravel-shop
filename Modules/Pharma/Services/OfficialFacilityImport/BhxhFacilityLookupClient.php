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
    public const DISTRICT_URL = 'https://baohiemxahoi.gov.vn/UserControls/BHXH/BaoHiemYTe/HienThiHoGiaDinh/AjaxPost.aspx/GetHuyenByLstmatinh';
    public const REFERER_URL = 'https://baohiemxahoi.gov.vn/tracuu/Pages/cskcb-ky-hop-dong-kham-chua-benh-bhyt.aspx';

    public function captcha(): array
    {
        try {
            $bootstrap = Http::timeout(15)->withHeaders($this->browserHeaders())->get(self::REFERER_URL);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kết nối cổng BHXH để khởi tạo phiên tra cứu.', previous: $exception);
        }

        if (! $bootstrap->successful()) {
            throw new RuntimeException('Cổng BHXH trả lỗi khi khởi tạo phiên tra cứu (HTTP '.$bootstrap->status().').');
        }

        $cookies = $this->extractCookies($bootstrap->headers()['Set-Cookie'] ?? []);

        try {
            $response = Http::timeout(15)->accept('*/*')->withHeaders(array_merge($this->browserHeaders(), [
                'Cookie' => $this->cookieHeader($cookies),
            ]))->get(self::CAPTCHA_URL);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kết nối cổng BHXH để tải CAPTCHA.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Cổng BHXH trả lỗi khi tải CAPTCHA (HTTP '.$response->status().').');
        }

        $cookies = array_merge($cookies, $this->extractCookies($response->headers()['Set-Cookie'] ?? []));

        return ['body' => $response->body(), 'content_type' => $response->header('Content-Type') ?: 'image/png', 'cookies' => $cookies];
    }

    public function districts(string $provinceCode): array
    {
        try {
            $response = Http::timeout(15)->withHeaders(array_merge($this->browserHeaders(), [
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Content-Type' => 'application/json; charset=UTF-8',
                'Origin' => 'https://baohiemxahoi.gov.vn',
                'X-Requested-With' => 'XMLHttpRequest',
            ]))->post(self::DISTRICT_URL, ['lstmatinh' => $provinceCode]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kết nối cổng BHXH để tải danh sách quận/huyện.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Cổng BHXH trả lỗi khi tải quận/huyện (HTTP '.$response->status().').');
        }

        $payload = $response->json();
        $data = is_array($payload) ? ($payload['d'] ?? $payload) : [];
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($data)) {
            return [];
        }

        $districts = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                continue;
            }
            $code = $this->firstValue($item, ['MAHUYEN', 'MaHuyen', 'maHuyen', 'mahuyen', 'Value', 'value', 'Code', 'code']);
            $name = $this->firstValue($item, ['TENHUYEN', 'TenHuyen', 'tenHuyen', 'tenhuyen', 'Text', 'text', 'Name', 'name']);
            if ($code !== '' && $name !== '') {
                $districts[] = ['code' => $code, 'name' => $name];
            }
        }

        return $districts;
    }

    public function lookup(string $provinceCode, ?string $districtCode, string $captcha, array $cookies): array
    {
        if ($cookies === []) {
            throw new RuntimeException('Phiên CAPTCHA BHXH chưa tồn tại hoặc đã hết hạn. Hãy tải CAPTCHA mới.');
        }

        $payload = http_build_query([
            'MaTinh' => $provinceCode,
            'MaQuanHuyen' => $districtCode ?? '',
            'tokenRecaptch' => $captcha,
        ], '', '&', PHP_QUERY_RFC1738);

        try {
            $response = Http::timeout(20)->withHeaders(array_merge($this->browserHeaders(), [
                'Accept' => 'text/html, */*; q=0.01',
                'Cookie' => $this->cookieHeader($cookies),
                'Origin' => 'https://baohiemxahoi.gov.vn',
                'X-Requested-With' => 'XMLHttpRequest',
            ]))->withBody($payload, 'application/x-www-form-urlencoded; charset=UTF-8')->post(self::LOOKUP_URL);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Không thể kết nối cổng BHXH để tra cứu cơ sở KCB.', previous: $exception);
        }

        if (! $response->successful()) {
            $diagnostic = $this->cleanText(strip_tags($response->body()));
            $suffix = $diagnostic !== '' ? ' '.$this->truncate($diagnostic, 180) : '';
            throw new RuntimeException('Cổng BHXH trả lỗi khi tra cứu cơ sở KCB (HTTP '.$response->status().').'.$suffix);
        }

        return [
            'facilities' => $this->parseFacilities($response->body()),
            'message' => $this->extractMessage($response->body()),
            'structure' => $this->inspectResponseStructure($response->body()),
        ];
    }

    public function parseFacilities(string $html): array
    {
        [$xpath] = $this->document($html);
        if (! $xpath) {
            return [];
        }

        $facilities = [];
        foreach ($xpath->query('//tr') ?: [] as $row) {
            $cells = $xpath->query('./td', $row);
            if ($cells === false || $cells->length < 3) {
                continue;
            }
            $externalId = $this->cleanText($cells->item(1)?->textContent);
            $name = $this->cleanText($cells->item(2)?->textContent);
            if ($externalId !== '' && $name !== '') {
                $facilities[] = ['external_id' => $externalId, 'facility_name' => $name];
            }
        }

        return $facilities;
    }

    /** Returns safe structural diagnostics only; never returns CAPTCHA/session data or raw HTML. */
    public function inspectResponseStructure(string $html): array
    {
        [$xpath] = $this->document($html);
        if (! $xpath) {
            return ['headers' => [], 'column_counts' => [], 'hidden_fields' => [], 'data_attributes' => []];
        }

        $headers = [];
        foreach ($xpath->query('//tr/th') ?: [] as $header) {
            $value = $this->cleanText($header->textContent);
            if ($value !== '') {
                $headers[] = $value;
            }
        }

        $columnCounts = [];
        foreach ($xpath->query('//tr[td]') ?: [] as $row) {
            $cells = $xpath->query('./td', $row);
            if ($cells !== false) {
                $columnCounts[$cells->length] = ($columnCounts[$cells->length] ?? 0) + 1;
            }
        }
        ksort($columnCounts);

        $hiddenFields = [];
        foreach ($xpath->query('//input[translate(@type,"HIDDEN","hidden")="hidden"]') ?: [] as $input) {
            $name = $this->cleanText($input->attributes?->getNamedItem('name')?->nodeValue);
            if ($name !== '') {
                $hiddenFields[] = $name;
            }
        }

        $dataAttributes = [];
        foreach ($xpath->query('//*[@*[starts-with(name(), "data-")]]') ?: [] as $node) {
            foreach ($node->attributes ?: [] as $attribute) {
                if (str_starts_with($attribute->nodeName, 'data-')) {
                    $dataAttributes[] = $attribute->nodeName;
                }
            }
        }

        return [
            'headers' => array_values(array_unique($headers)),
            'column_counts' => $columnCounts,
            'hidden_fields' => array_values(array_unique($hiddenFields)),
            'data_attributes' => array_values(array_unique($dataAttributes)),
        ];
    }

    private function document(string $html): array
    {
        if (trim($html) === '') {
            return [null, null];
        }
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return [new DOMXPath($document), $document];
    }

    private function extractMessage(string $html): ?string
    {
        $text = $this->cleanText(strip_tags($html));
        return $text === '' ? null : mb_substr($text, 0, 500);
    }

    private function browserHeaders(): array
    {
        return [
            'Referer' => self::REFERER_URL,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36',
            'Accept-Language' => 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache', 'Pragma' => 'no-cache',
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
            if (trim($name) !== '') {
                $cookies[trim($name)] = trim($value);
            }
        }
        return $cookies;
    }

    private function cookieHeader(array $cookies): string
    {
        return collect($cookies)->map(fn ($value, $name) => $name.'='.$value)->implode('; ');
    }

    private function firstValue(array $item, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && filled($item[$key])) {
                return $this->cleanText((string) $item[$key]);
            }
        }
        return '';
    }

    private function cleanText(?string $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }

    private function truncate(string $value, int $length): string
    {
        return mb_strlen($value) <= $length ? $value : mb_substr($value, 0, $length).'…';
    }
}
