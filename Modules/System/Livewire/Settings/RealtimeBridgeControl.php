<?php

namespace Modules\System\Livewire\Settings;

use App\Services\RealtimeManager;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SystemRealtimeControlService;
use Throwable;

class RealtimeBridgeControl extends Component
{
    use AuthorizesSystemActions;

    public bool $realtimeEnabled = false;

    public array $realtimeStatus = [];

    public bool $canUpdateRealtime = false;

    public function mount(): void
    {
        $this->canUpdateRealtime = (bool) auth('admin')->user()?->can('system.modules.update');
        $this->refreshRealtimeStatus();
    }

    public function toggleRealtime(RealtimeManager $realtime, SystemRealtimeControlService $control): void
    {
        $this->authorizePermission('system.modules.update');

        try {
            $control->toggle($realtime, $this->realtimeEnabled, auth('admin')->id());
            $this->refreshRealtimeStatus();
            $this->dispatch('notify', type: 'success', message: 'Đã cập nhật trạng thái Realtime / Socket.IO.');
        } catch (Throwable $e) {
            Log::warning('RealtimeBridgeControl mutation failed.', ['exception' => $e::class]);
            $this->dispatch('notify', type: 'error', message: 'Không thể cập nhật realtime. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function refreshRealtimeStatus(): void
    {
        $realtime = app(RealtimeManager::class);
        $this->realtimeEnabled = $realtime->enabled();

        try {
            $this->realtimeStatus = $realtime->health();
        } catch (Throwable $e) {
            Log::warning('RealtimeBridgeControl health check failed.', ['exception' => $e::class]);
            $this->realtimeStatus = ['ok' => false];
        }
    }

    public function render()
    {
        return view('System::livewire.settings.realtime-bridge-control');
    }
}
