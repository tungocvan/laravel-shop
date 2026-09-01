<?php

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Database\Seeders\AttendanceDemoSeeder;
use Modules\Attendance\Models\AttendanceAdjustmentRequest;
use Modules\Attendance\Models\AttendanceAuditEvent;
use Modules\Attendance\Models\AttendanceRecord;
use RuntimeException;

class AttendanceDemoDataService
{
    public function seed(): void
    {
        $this->guardEnvironment();

        app(AttendanceDemoSeeder::class)->run();
    }

    public function reset(): int
    {
        $this->guardEnvironment();

        return DB::transaction(function (): int {
            $recordIds = AttendanceRecord::query()
                ->where('session_key', 'like', 'demo-%')
                ->pluck('id');

            if ($recordIds->isEmpty()) {
                AttendanceAdjustmentRequest::query()->where('reason', 'like', '[DEMO]%')->delete();

                return 0;
            }

            AttendanceAdjustmentRequest::query()
                ->whereIn('attendance_record_id', $recordIds)
                ->orWhere('reason', 'like', '[DEMO]%')
                ->delete();

            AttendanceAuditEvent::query()->whereIn('attendance_record_id', $recordIds)->delete();

            return AttendanceRecord::query()->whereIn('id', $recordIds)->delete();
        });
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Attendance demo operations are available only in local/testing environments.');
        }
    }
}
