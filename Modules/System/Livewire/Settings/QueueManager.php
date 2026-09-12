<?php

namespace Modules\System\Livewire\Settings;

use App\Services\RealtimeManager;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Jobs\QueueProbeJob;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\QueueRegistryService;
use Modules\System\Services\SystemProcessManagerService;
use Modules\System\Services\SystemRealtimeControlService;
use Throwable;

class QueueManager extends Component
{
    use AuthorizesSystemActions;

    public bool $realtimeEnabled = false;

    public array $realtimeStatus = [];

    public bool $canUpdateRealtime = false;

    public bool $canManageProcesses = false;

    public function mount(): void
    {
        $this->canUpdateRealtime = (bool) auth('admin')->user()?->can('system.modules.update');
        $this->canManageProcesses = $this->canUpdateRealtime;
        $this->refreshRealtimeStatus();
    }

    public function toggleRealtime(RealtimeManager $realtime, SystemRealtimeControlService $control): void
    {
        $this->authorizePermission('system.modules.update');

        try {
            $control->toggle($realtime, $this->realtimeEnabled, auth('admin')->id());
            $this->refreshRealtimeStatus();
            session()->flash('message', 'Realtime Socket.IO đã được '.($this->realtimeEnabled ? 'bật' : 'tắt').'. Không cần build lại frontend.');
        } catch (Throwable $e) {
            Log::warning('QueueManager realtime mutation failed.', ['exception' => $e::class]);
            session()->flash('error', 'Không thể cập nhật realtime. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function refreshRealtimeStatus(): void
    {
        $realtime = app(RealtimeManager::class);
        $this->realtimeEnabled = $realtime->enabled();

        try {
            $this->realtimeStatus = $realtime->health();
        } catch (Throwable $e) {
            Log::warning('QueueManager realtime health check failed.', ['exception' => $e::class]);
            $this->realtimeStatus = ['ok' => false];
        }
    }

    public function processAction(string $name, string $action, SystemProcessManagerService $processes): void
    {
        $this->authorizePermission('system.modules.update');

        try {
            $processes->act($name, $action, auth('admin')->id());
            session()->flash('message', "Đã gửi lệnh {$action} cho {$name}.");
        } catch (Throwable $e) {
            Log::warning('QueueManager PM2 process mutation failed.', [
                'process' => $name,
                'action' => $action,
                'exception' => $e::class,
            ]);
            session()->flash('error', 'Không thể điều khiển tiến trình PM2. Vui lòng kiểm tra quyền PM2 và log hệ thống.');
        }
    }

    public function probe(string $queue): void
    {
        $this->authorizePermission('system.settings.view');

        $registry = app(QueueRegistryService::class);
        $knownQueues = collect($registry->queues())->pluck('name');
        abort_unless($knownQueues->contains($queue), 404);

        QueueProbeJob::dispatch($queue);

        session()->flash('queue_message', "Đã gửi probe vào queue {$queue}. Nếu worker đang chạy, trạng thái sẽ cập nhật sau vài giây.");
    }

    public function render()
    {
        $registry = app(QueueRegistryService::class);

        $queues = collect($registry->queues())
            ->map(function (array $queue) use ($registry) {
                return array_merge($queue, [
                    'status' => $registry->status($queue['name']),
                    'command' => $registry->command($queue),
                ]);
            })
            ->values()
            ->all();

        return view('System::livewire.settings.queue-manager', [
            'queues' => $queues,
            'processStatus' => app(SystemProcessManagerService::class)->status(),
        ]);
    }
}
