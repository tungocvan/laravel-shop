<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use Modules\Attendance\Models\AttendanceAuditEvent;
use Modules\Attendance\Models\AttendanceRecord;

class AttendanceAuditService
{
    public function record(
        string $action,
        ?int $actorUserId,
        AttendanceRecord $record,
        array $before = [],
        array $after = [],
        array $metadata = [],
        ?string $reason = null,
    ): AttendanceAuditEvent {
        return AttendanceAuditEvent::query()->create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'target_type' => AttendanceRecord::class,
            'target_id' => (string) $record->getKey(),
            'attendance_record_id' => $record->getKey(),
            'reason' => $reason,
            'before_json' => $this->sanitize($before),
            'after_json' => $this->sanitize($after),
            'metadata_json' => $this->sanitize($metadata),
            'created_at' => CarbonImmutable::now(),
        ]);
    }

    private function sanitize(array $payload): array
    {
        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->isRawGpsKey($normalizedKey)) {
                unset($payload[$key]);

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitize($value);
            }
        }

        return $payload;
    }

    private function isRawGpsKey(string $key): bool
    {
        return str_contains($key, 'latitude')
            || str_contains($key, 'longitude')
            || str_contains($key, 'accuracy_meters')
            || str_contains($key, 'captured_at');
    }
}
