<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Attendance\Enums\VerificationResult;
use Modules\Attendance\Models\AttendanceLocation;

class GeofenceService
{
    public function verify(
        float $latitude,
        float $longitude,
        float $accuracyMeters,
        CarbonImmutable $capturedAt,
        string $action,
    ): array {
        $this->validateCoordinates($latitude, $longitude, $accuracyMeters);

        if (! in_array($action, ['check_in', 'check_out'], true)) {
            throw new InvalidArgumentException('Unsupported attendance geofence action.');
        }

        $enabledColumn = $action === 'check_in' ? 'check_in_enabled' : 'check_out_enabled';
        $locations = AttendanceLocation::query()
            ->where('is_active', true)
            ->where($enabledColumn, true)
            ->get();

        if ($locations->isEmpty()) {
            return $this->result(null, $latitude, $longitude, $accuracyMeters, $capturedAt, null, VerificationResult::LocationUnavailable);
        }

        $nearest = null;
        $nearestDistance = null;

        foreach ($locations as $location) {
            $distance = $this->distanceMeters(
                $latitude,
                $longitude,
                (float) $location->latitude,
                (float) $location->longitude,
            );

            if ($nearestDistance === null || $distance < $nearestDistance) {
                $nearest = $location;
                $nearestDistance = $distance;
            }
        }

        if ($accuracyMeters > (float) $nearest->maximum_accuracy_meters) {
            return $this->result($nearest, $latitude, $longitude, $accuracyMeters, $capturedAt, $nearestDistance, VerificationResult::AccuracyLow);
        }

        $verification = $nearestDistance <= (float) $nearest->radius_meters
            ? VerificationResult::Verified
            : VerificationResult::OutsideArea;

        return $this->result($nearest, $latitude, $longitude, $accuracyMeters, $capturedAt, $nearestDistance, $verification);
    }

    public function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lon2 - $lon1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function validateCoordinates(float $latitude, float $longitude, float $accuracyMeters): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180.');
        }

        if (! is_finite($accuracyMeters) || $accuracyMeters < 0) {
            throw new InvalidArgumentException('Accuracy must be a non-negative finite value.');
        }
    }

    private function result(
        ?AttendanceLocation $location,
        float $latitude,
        float $longitude,
        float $accuracyMeters,
        CarbonImmutable $capturedAt,
        ?float $distanceMeters,
        VerificationResult $verification,
    ): array {
        return [
            'location' => $location,
            'location_id' => $location?->getKey(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => round($accuracyMeters, 2),
            'captured_at' => $capturedAt,
            'distance_meters' => $distanceMeters === null ? null : round($distanceMeters, 2),
            'verification_result' => $verification,
        ];
    }
}
