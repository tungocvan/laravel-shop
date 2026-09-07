<?php

namespace Modules\Partner\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DoanhNghiepLookupService
{
    public const BASE_URL = 'https://doanhnghiep.vn';

    public const SOURCE = 'doanhnghiep_vn';

    public function search(string $query): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $response = $this->http()->get(self::BASE_URL.'/api/v1/search', [
            'q' => $query,
            'limit' => 10,
        ]);

        if ($response->status() === 429) {
            throw new RuntimeException('Nguồn doanhnghiep.vn đang giới hạn tần suất tra cứu.');
        }

        if (! $response->successful()) {
            throw new RuntimeException("Nguồn doanhnghiep.vn trả về HTTP {$response->status()}.");
        }

        $payload = $response->json();
        $items = is_array($payload['items'] ?? null)
            ? $payload['items']
            : (is_array($payload['data'] ?? null) ? $payload['data'] : []);

        return collect($items)
            ->map(fn (array $item): array => $this->candidate($item))
            ->filter(fn (array $item): bool => $item['tax_code'] !== '' && $item['name'] !== '')
            ->values()
            ->all();
    }

    public function fetchDetail(string $taxCode): array
    {
        $taxCode = trim($taxCode);

        if (! preg_match('/^[0-9]{10}(?:[0-9]{3})?$/', $taxCode)) {
            throw new RuntimeException('Mã số thuế doanh nghiệp không hợp lệ.');
        }

        $response = $this->http()->get(self::BASE_URL.'/api/v1/companies/'.rawurlencode($taxCode));

        if ($response->status() === 404) {
            throw new RuntimeException("Không tìm thấy doanh nghiệp có MST {$taxCode}.");
        }

        if ($response->status() === 429) {
            throw new RuntimeException('Nguồn doanhnghiep.vn đang giới hạn tần suất tra cứu.');
        }

        if (! $response->successful()) {
            throw new RuntimeException("Nguồn doanhnghiep.vn trả về HTTP {$response->status()}.");
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Nguồn doanhnghiep.vn trả về dữ liệu không hợp lệ.');
        }

        return [
            'tax_address' => $this->string($data['address_full'] ?? null),
            'status' => $this->string($data['status'] ?? null),
            'representative' => $this->string($data['legal_rep_name'] ?? null),
            'active_since' => $this->string($data['registered_at'] ?? null),
            'managed_by' => $this->string($data['managed_by'] ?? null),
            'organization_type' => $this->string($data['legal_form'] ?? null),
            'industry' => $this->string(data_get($data, 'industry.name_vi')),
            'province_code' => $this->string(data_get($data, 'province.code')),
            'province_name' => $this->string(data_get($data, 'province.name_vi')),
            'source_url' => self::BASE_URL.'/dn/'.rawurlencode($taxCode),
            'raw' => $data,
        ];
    }

    private function candidate(array $item): array
    {
        $taxCode = $this->string($item['mst'] ?? $item['tax_code'] ?? '');
        $name = $this->string($item['name_vi'] ?? $item['name'] ?? '');

        return [
            'tax_code' => $taxCode,
            'name' => $name,
            'canonical_path' => $taxCode,
            'canonical_url' => $taxCode !== '' ? self::BASE_URL.'/dn/'.rawurlencode($taxCode) : null,
            'source' => self::SOURCE,
            'source_label' => 'Doanhnghiep.vn',
            'status' => $this->string($item['status'] ?? null),
            'address' => $this->string($item['address_full'] ?? null),
            'representative' => $this->string($item['legal_rep_name'] ?? null),
        ];
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(15)
            ->retry(2, 250, throw: false)
            ->withHeaders([
                'User-Agent' => 'laravel-shop-partner-registry/1.0',
            ]);
    }

    private function string(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
