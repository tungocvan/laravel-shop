<?php

namespace Modules\System\Livewire\Settings;

use App\Services\RealtimeManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Jobs\QueueProbeJob;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\QueueFailedJobService;
use Modules\System\Services\QueueRegistryService;
use Modules\System\Services\SystemRealtimeControlService;
use Throwable;

class QueueManager extends Component
{
    use AuthorizesSystemActions;

    public bool $realtimeEnabled = false;

    public array $realtimeStatus = [];

    public bool $canUpdateRealtime = false;

    public bool $canManageQueues = false;

    public ?string $failedQueue = null;

    public array $failedJobs = [];

    public array $selectedFailedIds = [];

    public function mount(): void
    {
        $admin = auth('admin')->user();
        $this->canUpdateRealtime = (bool) $admin?->can('system.modules.update');
        $this->canManageQueues = (bool) $admin?->can('system.settings.update');
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

    public function restartWorkers(): void
    {
        $this->authorizePermission('system.settings.update');

        try {
            Artisan::call('queue:restart');

            Log::notice('Queue workers restart requested from System Queue Manager.', [
                'actor_id' => auth('admin')->id(),
            ]);

            session()->flash('queue_message', 'Đã gửi tín hiệu restart đến queue workers. Worker sẽ khởi động lại an toàn sau khi hoàn tất job đang xử lý.');
        } catch (Throwable $e) {
            Log::warning('QueueManager queue restart failed.', ['exception' => $e::class]);
            session()->flash('error', 'Không thể gửi tín hiệu restart queue. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function openFailedJobs(string $queue, QueueFailedJobService $failedJobs): void
    {
        $this->authorizePermission('system.settings.view');
        $this->assertKnownQueue($queue);

        $this->failedQueue = $queue;
        $this->selectedFailedIds = [];
        $this->failedJobs = $failedJobs->list($queue);
    }

    public function closeFailedJobs(): void
    {
        $this->failedQueue = null;
        $this->failedJobs = [];
        $this->selectedFailedIds = [];
    }

    public function retrySelectedFailed(QueueFailedJobService $failedJobs): void
    {
        $this->authorizePermission('system.settings.update');

        if (! $this->failedQueue || $this->selectedFailedIds === []) {
            return;
        }

        try {
            $count = $failedJobs->retry($this->failedQueue, $this->selectedFailedIds);
            session()->flash('queue_message', "Đã gửi retry cho {$count} failed job của queue {$this->failedQueue}.");
            $this->refreshFailedJobs($failedJobs);
        } catch (Throwable $e) {
            Log::warning('QueueManager failed job retry failed.', ['exception' => $e::class]);
            session()->flash('error', 'Không thể retry failed jobs. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function forgetSelectedFailed(QueueFailedJobService $failedJobs): void
    {
        $this->authorizePermission('system.settings.update');

        if (! $this->failedQueue || $this->selectedFailedIds === []) {
            return;
        }

        try {
            $count = $failedJobs->forget($this->failedQueue, $this->selectedFailedIds);
            session()->flash('queue_message', "Đã xóa {$count} failed job khỏi lịch sử queue {$this->failedQueue}.");
            $this->refreshFailedJobs($failedJobs);
        } catch (Throwable $e) {
            Log::warning('QueueManager failed job forget failed.', ['exception' => $e::class]);
            session()->flash('error', 'Không thể xóa failed jobs đã chọn. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function clearFailedHistory(string $queue, QueueFailedJobService $failedJobs): void
    {
        $this->authorizePermission('system.settings.update');
        $this->assertKnownQueue($queue);

        try {
            $count = $failedJobs->clear($queue);
            session()->flash('queue_message', "Đã clear {$count} failed job trong lịch sử queue {$queue}.");

            if ($this->failedQueue === $queue) {
                $this->refreshFailedJobs($failedJobs);
            }
        } catch (Throwable $e) {
            Log::warning('QueueManager failed history clear failed.', ['exception' => $e::class]);
            session()->flash('error', 'Không thể clear lịch sử failed jobs. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function probe(string $queue): void
    {
        $this->authorizePermission('system.settings.view');
        $this->assertKnownQueue($queue);

        $registry = app(QueueRegistryService::class);
        $registry->markProbeSent($queue);
        QueueProbeJob::dispatch($queue);

        session()->flash('queue_message', "Đã gửi probe vào queue {$queue}. Nếu worker đang nghe đúng queue, trạng thái sẽ chuyển sang xác nhận sau vài giây.");
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

    private function refreshFailedJobs(QueueFailedJobService $failedJobs): void
    {
        if (! $this->failedQueue) {
            return;
        }

        $this->selectedFailedIds = [];
        $this->failedJobs = $failedJobs->list($this->failedQueue);
    }

    private function assertKnownQueue(string $queue): void
    {
        $knownQueues = collect(app(QueueRegistryService::class)->queues())->pluck('name');
        abort_unless($knownQueues->contains($queue), 404);
    }
}
