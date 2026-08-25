<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Request\Application\Services\EnforceRequestTaskSla as Enforcer;

class EnforceRequestTaskSla extends Command
{
    protected $signature = 'request:sla-enforce';

    protected $description = 'Kiểm tra cảnh báo, quá hạn và tạm dừng các tác vụ Request theo SLA';

    public function handle(Enforcer $enforcer): int
    {
        $result = $enforcer->handle();

        $this->components->info('Request SLA enforcement');
        $this->line('Cảnh báo: '.$result['warned']);
        $this->line('Quá hạn: '.$result['overdue']);
        $this->line('Tạm dừng: '.$result['suspended']);

        return self::SUCCESS;
    }
}
