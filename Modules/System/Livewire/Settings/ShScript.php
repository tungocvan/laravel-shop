<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SystemScriptOperationService;
use Throwable;

class ShScript extends Component
{
    use AuthorizesSystemActions;

    public ?string $selectedOperation = null;

    public string $executionOutput = '';

    public string $errorMessage = '';

    public function executeOperation(SystemScriptOperationService $service): void
    {
        $this->authorizePermission('system.commands.run');

        $this->executionOutput = '';
        $this->errorMessage = '';

        if ($this->selectedOperation === null || $this->selectedOperation === '') {
            $this->errorMessage = 'Vui lòng chọn một thao tác script đã được đăng ký.';

            return;
        }

        try {
            $result = $service->execute(
                $this->selectedOperation,
                auth('admin')->id() ?: auth()->id(),
            );

            $this->executionOutput = $result['output'] !== ''
                ? $result['output']
                : 'Thao tác script đã hoàn tất.';
        } catch (Throwable) {
            $this->errorMessage = 'Không thể thực hiện thao tác script. Vui lòng kiểm tra log.';
        }
    }

    public function render(SystemScriptOperationService $service)
    {
        return view('System::livewire.settings.sh-script', [
            'operations' => $service->operations(),
        ]);
    }
}
