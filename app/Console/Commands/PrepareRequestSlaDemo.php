<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestTask;

class PrepareRequestSlaDemo extends Command
{
    protected $signature = 'request:sla-demo {requestNumber} {state : warning|overdue|suspend}';

    protected $description = 'Đưa active task Request tới một mốc SLA giả lập để kiểm thử local';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->components->error('request:sla-demo không được phép chạy trên production.');

            return self::FAILURE;
        }

        $state = (string) $this->argument('state');
        if (! in_array($state, ['warning', 'overdue', 'suspend'], true)) {
            throw ValidationException::withMessages(['state' => ['Chỉ hỗ trợ warning, overdue hoặc suspend.']]);
        }

        $request = InternalRequest::query()
            ->where('request_number', (string) $this->argument('requestNumber'))
            ->firstOrFail();

        if (! $request->current_run_id) {
            $this->components->error('Đề nghị chưa có current run để kiểm thử SLA.');

            return self::FAILURE;
        }

        $task = RequestTask::query()
            ->where('request_run_id', $request->current_run_id)
            ->where('status', TaskStatus::Active)
            ->whereNull('closed_at')
            ->orderBy('stage_position')
            ->orderBy('id')
            ->first();

        if (! $task) {
            $this->components->error('Không tìm thấy active task cho đề nghị này.');

            return self::FAILURE;
        }

        $now = now('UTC');
        $snapshot = (array) $task->sla_snapshot_json;
        unset($snapshot['warning_emitted_at']);
        $snapshot['timeout_action'] = 'suspend';
        $snapshot['demo_state'] = $state;

        $times = match ($state) {
            'warning' => [
                'warning_at' => $now->copy()->subMinute(),
                'due_at' => $now->copy()->addHour(),
                'grace_expires_at' => $now->copy()->addHours(2),
            ],
            'overdue' => [
                'warning_at' => $now->copy()->subHours(2),
                'due_at' => $now->copy()->subMinute(),
                'grace_expires_at' => $now->copy()->addHour(),
            ],
            'suspend' => [
                'warning_at' => $now->copy()->subHours(3),
                'due_at' => $now->copy()->subHours(2),
                'grace_expires_at' => $now->copy()->subMinute(),
            ],
        };

        $task->forceFill($times + [
            'sla_snapshot_json' => $snapshot,
            'overdue_at' => null,
            'suspended_at' => null,
        ])->save();

        $this->components->info('Đã chuẩn bị SLA demo: '.$state);
        $this->line('Request: '.$request->request_number);
        $this->line('Task: '.$task->public_id);
        $this->line('warning_at: '.$task->fresh()->warning_at?->toIso8601String());
        $this->line('due_at: '.$task->fresh()->due_at?->toIso8601String());
        $this->line('grace_expires_at: '.$task->fresh()->grace_expires_at?->toIso8601String());
        $this->newLine();
        $this->line('Chạy tiếp: php artisan request:sla-enforce');

        return self::SUCCESS;
    }
}
