<?php

namespace Modules\Partner\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Partner\Contracts\BusinessRegistryProvider;
use RuntimeException;

class MstCongTyProvider implements BusinessRegistryProvider
{
    public const BASE_URL = 'https://mstcongty.com';

    public const SOURCE = 'mstcongty';

    public function source(): string
    {
        return self::SOURCE;
    }

    public function label(): string
    {
        return 'MSTCongTy';
    }

    public function search(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $response = $this->http()->get(self::BASE_URL.'/tim-kiem', ['q' => $query]);

        if (! $response->successful()) {
            throw new RuntimeException("MSTCongTy trả về HTTP {$response->status()}.");
        }

        $html = $response->body();
        $this->guardChallenge($html);

        preg_match_all(
            '~href="(?<path>/((?<tax>[0-9]{10}(?:-[0-9]{3})?)-[^"?#]+))"[^>]*>\s*<h3[^>]*>(?<name>.*?)</h3>.*?<span[^>]*>MST:\s*(?:<!--.*?-->)?\s*(?<shown>[0-9-]+)</span>.*?<span>(?<address>.*?)</span>.*?<span[^>]*>(?<status>.*?)</span>~si',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        return collect($matches)
            ->map(fn (array $match): array => [
                'tax_code' => trim(strip_tags($match['shown'] ?: $match['tax'])),
                'name' => html_entity_decode(trim(strip_tags($match['name'])), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'address' => html_entity_decode(trim(strip_tags($match['address'])), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'status' => html_entity_decode(trim(strip_tags($match['status'])), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'canonical_path' => $match['path'],
                'canonical_url' => self::BASE_URL.$match['path'],
                'source' => self::SOURCE,
                'source_label' => $this->label(),
            ])
            ->unique(fn (array $item) => $item['tax_code'].'|'.$item['canonical_path'])
            ->values()
            ->all();
    }

    public function fetchDetail(array $candidate): array
    {
        $path = (string) ($candidate['canonical_path'] ?? '');

        if (! preg_match('~^/[0-9]{10}(?:-[0-9]{3})?-[a-z0-9-]+$~i', $path)) {
            throw new RuntimeException('URL chi tiết MSTCongTy không hợp lệ.');
        }

        $response = $this->http()->get(self::BASE_URL.$path);

        if (! $response->successful()) {
            throw new RuntimeException("MSTCongTy trả về HTTP {$response->status()}.");
        }

        $html = $response->body();
        $this->guardChallenge($html);

        return [
            'tax_address' => $this->field($html, ['Địa chỉ thuế', 'Địa chỉ']),
            'status' => $this->field($html, ['Trạng thái']),
            'representative' => $this->field($html, ['Người đại diện', 'Đại diện pháp luật']),
            'active_since' => $this->field($html, ['Ngày hoạt động', 'Ngày cấp']),
            'managed_by' => $this->field($html, ['Cơ quan quản lý thuế', 'Quản lý bởi']),
            'organization_type' => $this->field($html, ['Loại hình DN', 'Loại hình doanh nghiệp']),
            'industry' => $this->field($html, ['Ngành nghề chính', 'Ngành nghề']),
            'province_code' => null,
            'province_name' => null,
            'source_url' => self::BASE_URL.$path,
            'raw' => ['html_snapshot_hash' => hash('sha256', $html)],
        ];
    }

    private function field(string $html, array $labels): ?string
    {
        foreach ($labels as $label) {
            $quoted = preg_quote($label, '~');
            $patterns = [
                '~<[^>]+>\s*'.$quoted.'\s*</[^>]+>\s*<[^>]+>(.*?)</[^>]+>~si',
                '~'.$quoted.'\s*</[^>]+>\s*<[^>]+>(.*?)</[^>]+>~si',
                '~'.$quoted.'\s*:?\s*</?[^>]*>\s*([^<]{2,500})~si',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $match)) {
                    $value = html_entity_decode(trim(strip_tags($match[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if ($value !== '') {
                        return preg_replace('/\s+/u', ' ', $value);
                    }
                }
            }
        }

        return null;
    }

    private function guardChallenge(string $html): void
    {
        if (str_contains($html, 'Just a moment...') || str_contains($html, 'cf-chl-') || str_contains($html, 'challenge-platform/h/g/')) {
            throw new RuntimeException('MSTCongTy đang yêu cầu browser challenge; provider tạm thời không khả dụng.');
        }
    }

    private function http(): PendingRequest
    {
        return Http::accept('text/html,application/xhtml+xml')
            ->timeout(15)
            ->retry(1, 250, throw: false)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; LaravelShopPartnerLookup/1.0)',
                'Accept-Language' => 'vi-VN,vi;q=0.9,en;q=0.7',
            ]);
    }
}
