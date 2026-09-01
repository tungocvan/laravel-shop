<?php

namespace Modules\Attendance\Services;

use DomainException;
use Illuminate\Support\Facades\Http;

class AttendanceGeocodingService
{
    public function geocode(string $address): array
    {
        $address = trim($address);

        if ($address === '') {
            throw new DomainException('Vui lòng nhập địa chỉ cần tìm.');
        }

        $response = Http::acceptJson()
            ->withUserAgent(config('app.name', 'Laravel').' Attendance Admin Geocoder')
            ->timeout(8)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'jsonv2',
                'limit' => 1,
                'addressdetails' => 1,
            ]);

        if (! $response->successful()) {
            throw new DomainException('Không thể kết nối dịch vụ bản đồ. Vui lòng thử lại sau.');
        }

        $result = $response->json('0');

        if (! is_array($result) || ! isset($result['lat'], $result['lon'])) {
            throw new DomainException('Không tìm thấy tọa độ phù hợp với địa chỉ này.');
        }

        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'display_name' => (string) ($result['display_name'] ?? $address),
        ];
    }
}
