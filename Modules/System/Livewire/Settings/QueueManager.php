<?php

namespace Modules\System\Livewire\Settings;

use App\Services\RealtimeManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Jobs\QueueProbeJob;
use Modules\System\Services\QueueRegistryService;
use Modules\System\Services\SystemRealtimeControlService;
use Throwable;

class QueueManager extends Component
{
    public bool $realtimeEnabled = false;

    public array $realtimeStatus = [];

    public bool $canUpdateRealtime = false;

    public function mount(): void
    {
        $this->canUpdateRealtime = (bool) Auth::guard('admin')->user()?->can('system.modules.update');
        $this->refreshRealtimeStatus();
    }

    public function toggleRealtime(RealtimeManager $realtime, SystemRealtimeControlService $control): void
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin && $admin->can('system.modules.update'), 403);

        try {
            $control->toggle($realtime, $this->realtimeEnabled, $admin->id);
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

    public function probe(string $queue): void
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin && $admin->can('system.settings.view'), 403);

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
        ]);
    }
}
