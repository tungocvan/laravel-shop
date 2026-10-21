<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class VietQrTaxLookupService
{
    public const BASE_URL = 'https://api.vietqr.io/v2/business/';

    public function lookup(string $taxCode): ?array
    {
        $taxCode = trim($taxCode);
        if (! preg_match('/^[0-9]{10}(?:[0-9]{3})?$/', $taxCode)) {
            throw new RuntimeException('Mã số thuế phải gồm 10 hoặc 13 chữ số.');
        }

        $response = Http::acceptJson()->timeout(12)->retry(1, 250, throw: false)
            ->get(self::BASE_URL.$taxCode);

        if ($response->status() === 429) {
            throw new RuntimeException('VietQR giới hạn tần suất tra cứu.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('VietQR không khả dụng (HTTP '.$response->status().').');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('VietQR trả về dữ liệu không hợp lệ.');
        }

        if (in_array((string) ($payload['code'] ?? ''), ['51', '52'], true)) {
            return null;
        }

        $data = $payload['data'] ?? null;
        if ((string) ($payload['code'] ?? '') !== '00' || ! is_array($data)) {
            throw new RuntimeException('VietQR trả về trạng thái không hợp lệ.');
        }

        if ((string) ($data['id'] ?? '') !== $taxCode || trim((string) ($data['name'] ?? '')) === '') {
            throw new RuntimeException('VietQR trả về thông tin không khớp mã số thuế.');
        }

        return [
            'tax_code' => $taxCode,
            'name' => trim((string) $data['name']),
            'address' => trim((string) ($data['address'] ?? '')),
            'source' => 'vietqr',
            'source_label' => 'VietQR',
        ];
    }
}
