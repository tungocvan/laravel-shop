<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

        $raw = DB::table('request_tasks')->where('id', $task->id)->first(['warning_at', 'due_at', 'grace_expires_at']);
        $fresh = $task->fresh();
        $displayTimezone = (string) config('app.timezone', 'UTC');

        $this->components->info('Đã chuẩn bị SLA demo: '.$state);
        $this->line('Request: '.$request->request_number);
        $this->line('Task: '.$task->public_id);
        $this->line('DB UTC warning_at: '.($raw->warning_at ?? '-'));
        $this->line('DB UTC due_at: '.($raw->due_at ?? '-'));
        $this->line('DB UTC grace_expires_at: '.($raw->grace_expires_at ?? '-'));
        $this->line('UI '.$displayTimezone.' warning_at: '.$fresh->warning_at?->timezone($displayTimezone)->toIso8601String());
        $this->line('UI '.$displayTimezone.' due_at: '.$fresh->due_at?->timezone($displayTimezone)->toIso8601String());
        $this->line('UI '.$displayTimezone.' grace_expires_at: '.$fresh->grace_expires_at?->timezone($displayTimezone)->toIso8601String());
        $this->newLine();
        $this->line('Chạy tiếp: php artisan request:sla-enforce');

        return self::SUCCESS;
    }
}
