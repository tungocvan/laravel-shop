<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SystemOperationService;
use Throwable;

class ArtisanList extends Component
{
    use AuthorizesSystemActions;

    public string $selectedOperation = 'artisan.list';

    public string $commandOutput = '';

    public string $errorMessage = '';

    public function executeOperation(SystemOperationService $service): void
    {
        $this->authorizePermission('system.commands.run');

        $this->commandOutput = '';
        $this->errorMessage = '';

        try {
            $result = $service->execute(
                $this->selectedOperation,
                auth('admin')->id() ?: auth()->id(),
            );

            $this->commandOutput = $result['output'] !== ''
                ? $result['output']
                : 'Thao tác đã hoàn tất.';
        } catch (Throwable) {
            $this->errorMessage = 'Không thể thực hiện thao tác hệ thống. Vui lòng kiểm tra log.';
        }
    }

    public function render(SystemOperationService $service)
    {
        return view('System::livewire.settings.artisan-list', [
            'operations' => $service->operations(),
        ]);
    }
}
