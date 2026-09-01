<?php

namespace Modules\Attendance\Services;

use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AttendanceGeocodingService
{
    public function geocode(string $address): array
    {
        $address = trim($address);

        if ($address === '') {
            throw new DomainException('Vui lòng nhập địa chỉ cần tìm.');
        }

        if (! config('attendance.attendance.geocoding.enabled', true)) {
            throw new DomainException('Tìm tọa độ từ địa chỉ hiện đang tắt. Bạn vẫn có thể nhập tọa độ hoặc lấy vị trí hiện tại.');
        }

        try {
            $response = Http::acceptJson()
                ->withUserAgent(config('app.name', 'Laravel').' Attendance Admin Geocoder')
                ->timeout(max(1, (int) config('attendance.attendance.geocoding.timeout_seconds', 8)))
                ->get((string) config('attendance.attendance.geocoding.endpoint'), [
                    'q' => $address,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);
        } catch (ConnectionException) {
            throw new DomainException('Không thể kết nối dịch vụ bản đồ. Bạn vẫn có thể nhập tọa độ hoặc lấy vị trí hiện tại.');
        }

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
