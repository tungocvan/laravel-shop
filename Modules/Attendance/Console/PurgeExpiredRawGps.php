<?php

namespace Modules\Attendance\Console;

use Illuminate\Console\Command;
use Modules\Attendance\Services\AttendancePrivacyRetentionService;

class PurgeExpiredRawGps extends Command
{
    protected $signature = 'attendance:privacy-purge';

    protected $description = 'Remove expired precise GPS evidence from Attendance records';

    public function handle(AttendancePrivacyRetentionService $service): int
    {
        $updated = $service->purgeExpiredRawGps();

        $this->info("Attendance GPS privacy cleanup completed: {$updated} record(s) updated.");

        return self::SUCCESS;
    }
}
