<?php

namespace Modules\System\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\System\Jobs\QueueProbeJob;
use Modules\System\Services\QueueRegistryService;

class QueueManager extends Component
{
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
