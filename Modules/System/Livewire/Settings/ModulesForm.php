<?php

namespace Modules\System\Livewire\Settings;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use LogicException;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SystemModuleControlService;
use Modules\System\Services\SystemModuleOverviewService;
use Throwable;

class ModulesForm extends Component
{
    use AuthorizesSystemActions;

    public array $modules = [];

    public bool $canUpdate = false;

    public function mount(): void
    {
        $this->canUpdate = (bool) auth('admin')->user()?->can('system.modules.update');
        $this->loadModules();
    }

    public function loadModules(): void
    {
        $this->modules = app(SystemModuleOverviewService::class)->rows();
    }

    public function toggleModule(string $moduleName, SystemModuleControlService $control): void
    {
        $this->authorizePermission('system.modules.update');

        try {
            $result = $control->toggle($moduleName, auth('admin')->id());
            $this->loadModules();

            $suffix = $result['enabled'] && $result['migrated'] ? ' và đã migrate database' : '';
            $suffix .= $result['enabled'] && $result['permission_count'] > 0
                ? "; đã đồng bộ {$result['permission_count']} quyền"
                : '';

            session()->flash('message', 'Module '.$moduleName.' đã được '.($result['enabled'] ? 'bật' : 'tắt').$suffix.'.');
        } catch (LogicException $e) {
            Log::notice('ModulesForm module toggle rejected by lifecycle rule.', [
                'module' => $moduleName,
                'exception' => $e::class,
                'reason' => $e->getMessage(),
            ]);
            session()->flash('error', "Không thể thay đổi trạng thái module {$moduleName} do ràng buộc hệ thống.");
        } catch (Throwable $e) {
            Log::warning('ModulesForm module toggle failed.', [
                'module' => $moduleName,
                'exception' => $e::class,
            ]);
            session()->flash('error', "Không thể cập nhật module {$moduleName}. Vui lòng kiểm tra log hệ thống.");
        }
    }

    public function render()
    {
        return view('System::livewire.settings.modules-form');
    }
}
