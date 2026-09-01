<?php

namespace Modules\Attendance\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\AttendanceLocation;
use Modules\Attendance\Models\AttendanceShift;

class AttendanceAdminConfigService
{
    public function saveShift(AttendanceShift $shift, array $attributes): AttendanceShift
    {
        $lateGrace = (int) ($attributes['late_grace_minutes'] ?? 0);
        $earlyGrace = (int) ($attributes['early_leave_grace_minutes'] ?? 0);

        if ($lateGrace < 0 || $earlyGrace < 0) {
            throw new DomainException('Attendance grace minutes cannot be negative.');
        }

        return DB::transaction(function () use ($shift, $attributes): AttendanceShift {
            if (($attributes['is_default'] ?? false) && ($attributes['is_active'] ?? $shift->is_active ?? true)) {
                AttendanceShift::query()
                    ->whereKeyNot($shift->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $shift->fill($attributes)->save();

            return $shift->refresh();
        });
    }

    public function saveLocation(AttendanceLocation $location, array $attributes): AttendanceLocation
    {
        $latitude = (float) ($attributes['latitude'] ?? $location->latitude);
        $longitude = (float) ($attributes['longitude'] ?? $location->longitude);
        $radius = (int) ($attributes['radius_meters'] ?? $location->radius_meters);
        $accuracy = (int) ($attributes['maximum_accuracy_meters'] ?? $location->maximum_accuracy_meters);

        if ($latitude < -90 || $latitude > 90) {
            throw new DomainException('Attendance latitude must be between -90 and 90.');
        }
        if ($longitude < -180 || $longitude > 180) {
            throw new DomainException('Attendance longitude must be between -180 and 180.');
        }
        if ($radius <= 0 || $radius > 10000) {
            throw new DomainException('Attendance radius must be between 1 and 10000 meters.');
        }
        if ($accuracy <= 0 || $accuracy > 1000) {
            throw new DomainException('Maximum GPS accuracy must be between 1 and 1000 meters.');
        }

        $location->fill($attributes)->save();

        return $location->refresh();
    }
}
