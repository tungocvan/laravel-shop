<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\Env\EnvSnapshotService;
use Throwable;

class EnvManager extends Component
{
    use AuthorizesSystemActions;

    public bool $canUpdate = false;

    public function mount(): void
    {
        $user = auth('admin')->user() ?: auth()->user();
        $this->canUpdate = (bool) $user?->can('system.env.update');
    }

    public function createSnapshot(string $operation, EnvSnapshotService $service): void
    {
        $this->authorizePermission('system.env.update');

        try {
            $result = $service->create(
                $operation,
                (auth('admin')->user() ?: auth()->user())?->getAuthIdentifier()
            );

            $this->dispatch(
                'notify',
                type: 'success',
                message: "Đã tạo snapshot {$result['label']} an toàn."
            );
        } catch (Throwable $e) {
            report($e);

            $this->dispatch(
                'notify',
                type: 'error',
                message: 'Không thể tạo snapshot ENV. Vui lòng kiểm tra log hệ thống.'
            );
        }
    }

    public function render()
    {
        return view('System::livewire.settings.env-manager');
    }
}
