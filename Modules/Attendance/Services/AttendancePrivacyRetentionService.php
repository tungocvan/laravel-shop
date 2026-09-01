<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use Modules\Attendance\Models\AttendanceRecord;

class AttendancePrivacyRetentionService
{
    public function purgeExpiredRawGps(?CarbonImmutable $now = null): int
    {
        $retentionDays = max(1, (int) config('attendance.attendance.privacy.raw_gps_retention_days', 30));
        $cutoff = ($now ?? CarbonImmutable::now())->subDays($retentionDays);
        $updated = 0;

        AttendanceRecord::query()
            ->where(function ($query) use ($cutoff): void {
                $query->where(function ($checkIn) use ($cutoff): void {
                    $checkIn->whereNotNull('check_in_captured_at')
                        ->where('check_in_captured_at', '<', $cutoff);
                })->orWhere(function ($checkOut) use ($cutoff): void {
                    $checkOut->whereNotNull('check_out_captured_at')
                        ->where('check_out_captured_at', '<', $cutoff);
                });
            })
            ->orderBy('id')
            ->chunkById(200, function ($records) use ($cutoff, &$updated): void {
                foreach ($records as $record) {
                    $changes = [];

                    if ($record->check_in_captured_at?->lt($cutoff)) {
                        $changes += [
                            'check_in_latitude' => null,
                            'check_in_longitude' => null,
                            'check_in_accuracy_meters' => null,
                            'check_in_captured_at' => null,
                        ];
                    }

                    if ($record->check_out_captured_at?->lt($cutoff)) {
                        $changes += [
                            'check_out_latitude' => null,
                            'check_out_longitude' => null,
                            'check_out_accuracy_meters' => null,
                            'check_out_captured_at' => null,
                        ];
                    }

                    if ($changes !== []) {
                        $record->forceFill($changes)->saveQuietly();
                        $updated++;
                    }
                }
            });

        return $updated;
    }
}
