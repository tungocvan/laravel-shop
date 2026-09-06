<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MasothueLookupService
{
    public const BASE_URL = 'https://masothue.com';

    public function lookup(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            throw new RuntimeException('Tu khoa tra cuu khong duoc de trong.');
        }

        $results = $this->search($query);
        $match = $this->selectBestMatch($query, $results);

        if ($match === null) {
            throw new RuntimeException("Khong tim thay ket qua cho: {$query}");
        }

        $detail = $this->fetchDetail($match['canonical_path']);

        return array_merge($match, $detail, [
            'query' => $query,
            'source' => self::BASE_URL,
            'source_checked_at' => now()->toIso8601String(),
        ]);
    }

    public function search(string $query): array
    {
        $response = $this->http()->get(self::BASE_URL.'/Search/', [
            'q' => $query,
            'type' => 'auto',
            'force-search' => 1,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("MaSoThue search tra ve HTTP {$response->status()}.");
        }

        $xpath = $this->xpath($response->body());
        $results = [];

        foreach ($xpath->query('//h3/a[@href]') as $anchor) {
            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $path = trim($anchor->getAttribute('href'));

            if (! preg_match('#^/([0-9]{10}(?:-[0-9]{3})?)-#', $path, $matches)) {
                continue;
            }

            $name = $this->cleanText($anchor->textContent);

            if ($name === '') {
                continue;
            }

            $results[] = [
                'tax_code' => $matches[1],
                'name' => $name,
                'canonical_path' => $path,
                'canonical_url' => self::BASE_URL.$path,
            ];
        }

        return $results;
    }

    public function fetchDetail(string $canonicalPath): array
    {
        if (! str_starts_with($canonicalPath, '/')) {
            throw new RuntimeException('Canonical path MaSoThue khong hop le.');
        }

        $response = $this->http()->get(self::BASE_URL.$canonicalPath);

        if (! $response->successful()) {
            throw new RuntimeException("Trang chi tiet MaSoThue tra ve HTTP {$response->status()}.");
        }

        $xpath = $this->xpath($response->body());
        $fields = [];

        foreach ($xpath->query('//tr[td]') as $row) {
            $cells = $xpath->query('./td', $row);

            if ($cells->length < 2) {
                continue;
            }

            $label = $this->normalizeLabel($cells->item(0)?->textContent ?? '');
            $value = $this->cleanText($cells->item(1)?->textContent ?? '');

            if ($label !== '' && $value !== '') {
                $fields[$label] = $value;
            }
        }

        return [
            'tax_address' => $this->field($fields, 'dia chi thue'),
            'status' => $this->field($fields, 'tinh trang'),
            'representative' => $this->field($fields, 'nguoi dai dien'),
            'active_since' => $this->field($fields, 'ngay hoat dong'),
            'managed_by' => $this->field($fields, 'quan ly boi'),
            'organization_type' => $this->field($fields, 'loai hinh dn'),
        ];
    }

    protected function selectBestMatch(string $query, array $results): ?array
    {
        if ($results === []) {
            return null;
        }

        $normalizedQuery = $this->normalizeName($query);

        foreach ($results as $result) {
            if ($this->normalizeName($result['name']) === $normalizedQuery) {
                return $result + ['match_type' => 'exact'];
            }
        }

        return $results[0] + ['match_type' => 'first_result'];
    }

    protected function http(): PendingRequest
    {
        return Http::timeout(15)
            ->accept('text/html,application/xhtml+xml')
            ->withUserAgent('laravel-shop-mst-lookup/1.0');
    }

    protected function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('Khong the parse HTML tu MaSoThue.');
        }

        return new DOMXPath($document);
    }

    protected function cleanText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    protected function normalizeName(string $value): string
    {
        return mb_strtolower($this->cleanText($value), 'UTF-8');
    }

    protected function normalizeLabel(string $value): string
    {
        $value = mb_strtolower($this->cleanText($value), 'UTF-8');
        $map = [
            'đ' => 'd', 'Đ' => 'd',
            'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a', 'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a', 'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e', 'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o', 'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o', 'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u', 'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        ];

        return strtr($value, $map);
    }

    protected function field(array $fields, string $key): ?string
    {
        return $fields[$key] ?? null;
    }
}
