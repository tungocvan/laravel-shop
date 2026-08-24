<?php

namespace App\Console\Commands;

use App\Modules\RequestReleaseReadinessChecker;
use Illuminate\Console\Command;

class CheckRequestReleaseReadiness extends Command
{
    protected $signature = 'request:release-readiness';

    protected $description = 'Kiểm tra điều kiện sẵn sàng phát hành và bật module Request';

    public function handle(RequestReleaseReadinessChecker $checker): int
    {
        $result = $checker->check();

        $this->components->info('Request release readiness');

        foreach ($result['checks'] as $name => $check) {
            $status = $check['passed'] ? 'PASS' : 'FAIL';
            $this->line(sprintf('%-5s %-24s %s', $status, $name, $check['detail']));
        }

        if ($result['ready']) {
            $this->newLine();
            $this->info('READY: Các điều kiện application-level của Request đã đạt.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('NOT READY: Còn điều kiện application-level chưa đạt.');

        return self::FAILURE;
    }
}
