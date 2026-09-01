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
        foreach (array_keys($payload) as $key) {
            if (str_contains((string) $key, 'latitude') || str_contains((string) $key, 'longitude')) {
                unset($payload[$key]);
            }
        }

        return $payload;
    }
}
