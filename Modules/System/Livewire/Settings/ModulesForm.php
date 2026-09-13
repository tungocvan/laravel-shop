<?php

namespace Modules\System\Livewire\Settings;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use LogicException;
use Modules\System\Exceptions\SystemModuleLifecycleException;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SystemModuleControlService;
use Modules\System\Services\SystemModuleLifecyclePreviewService;
use Modules\System\Services\SystemModuleOverviewService;
use Throwable;

class ModulesForm extends Component
{
    use AuthorizesSystemActions;

    public array $modules = [];

    public bool $canUpdate = false;

    public bool $lifecycleModalOpen = false;

    public ?string $lifecycleModule = null;

    public array $lifecyclePreflight = [];

    public ?array $lifecycleResult = null;

    public function mount(): void
    {
        $this->canUpdate = (bool) auth('admin')->user()?->can('system.modules.update');
        $this->loadModules();
    }

    public function loadModules(): void
    {
        $this->modules = app(SystemModuleOverviewService::class)->rows();
    }

    public function toggleModule(string $moduleName, SystemModuleLifecyclePreviewService $preview): void
    {
        $this->authorizePermission('system.modules.update');

        $this->lifecycleModule = $moduleName;
        $this->lifecycleResult = null;
        $this->lifecycleModalOpen = true;

        try {
            $this->lifecyclePreflight = $preview->preview($moduleName);
        } catch (Throwable $e) {
            Log::warning('ModulesForm lifecycle preflight failed.', [
                'module' => $moduleName,
                'exception' => $e::class,
            ]);

            $this->lifecyclePreflight = [];
            $this->lifecycleResult = [
                'ok' => false,
                'stage' => 'preflight',
                'title' => 'Không thể kiểm tra Module',
                'message' => 'Không thể hoàn tất kiểm tra trước khi thay đổi trạng thái Module.',
                'guidance' => 'Hãy kiểm tra log hệ thống, database và manifest của Module rồi thử lại.',
            ];
        }
    }

    public function confirmLifecycleToggle(
        SystemModuleControlService $control,
        SystemModuleLifecyclePreviewService $preview,
    ): void {
        $this->authorizePermission('system.modules.update');

        if (! $this->lifecycleModule) {
            return;
        }

        $moduleName = $this->lifecycleModule;

        try {
            $freshPreflight = $preview->preview($moduleName);
            $this->lifecyclePreflight = $freshPreflight;

            if (! ($freshPreflight['can_execute'] ?? false)) {
                $this->lifecycleResult = [
                    'ok' => false,
                    'stage' => 'preflight',
                    'title' => 'Chưa thể thực hiện',
                    'message' => 'Preflight phát hiện điều kiện chưa an toàn để thay đổi trạng thái Module.',
                    'guidance' => implode(' ', (array) ($freshPreflight['blocking'] ?? [])),
                ];

                return;
            }

            $result = $control->toggle($moduleName, auth('admin')->id());
            $this->loadModules();

            $this->lifecycleResult = [
                'ok' => true,
                'stage' => 'completed',
                'title' => $result['enabled'] ? 'Bật Module thành công' : 'Tắt Module thành công',
                'message' => $result['enabled']
                    ? 'Module đã vượt qua migration, permission sync và được ghi trạng thái runtime.'
                    : 'Module đã được tắt ở runtime. Dữ liệu và lịch sử queue được giữ nguyên.',
                'guidance' => ($result['queues'] ?? []) !== []
                    ? 'Queue ownership đã cập nhật theo trạng thái Module. Laravel đã gửi tín hiệu restart worker; process manager bên ngoài vẫn chịu trách nhiệm khởi động worker.'
                    : 'Module không khai báo queue riêng nên queue runtime không bị ảnh hưởng.',
                'enabled' => (bool) $result['enabled'],
                'migrated' => (bool) $result['migrated'],
                'permission_count' => (int) $result['permission_count'],
                'queues' => (array) ($result['queues'] ?? []),
                'queue_restart_requested' => (bool) ($result['queue_restart_requested'] ?? false),
            ];
        } catch (SystemModuleLifecycleException $e) {
            Log::warning('ModulesForm module lifecycle failed.', [
                'module' => $moduleName,
                'stage' => $e->stage,
                'exception' => $e::class,
            ]);

            $this->lifecycleResult = [
                'ok' => false,
                'stage' => $e->stage,
                'title' => 'Không thể thay đổi trạng thái Module',
                ...$e->reportPayload(),
            ];
        } catch (LogicException $e) {
            Log::notice('ModulesForm module toggle rejected by lifecycle rule.', [
                'module' => $moduleName,
                'exception' => $e::class,
            ]);

            try {
                $this->lifecyclePreflight = $preview->preview($moduleName);
            } catch (Throwable) {
                // Giữ preflight trước đó; không đưa raw exception ra browser.
            }

            $this->lifecycleResult = [
                'ok' => false,
                'stage' => 'dependency',
                'title' => 'Bị chặn bởi ràng buộc Module',
                'message' => 'Trạng thái Module đã thay đổi hoặc dependency hiện không cho phép thao tác này.',
                'guidance' => implode(' ', (array) ($this->lifecyclePreflight['blocking'] ?? ['Kiểm tra dependency và thử lại.'])),
            ];
        } catch (Throwable $e) {
            Log::warning('ModulesForm module toggle failed.', [
                'module' => $moduleName,
                'exception' => $e::class,
            ]);

            $this->lifecycleResult = [
                'ok' => false,
                'stage' => 'unknown',
                'title' => 'Không thể cập nhật Module',
                'message' => 'Thao tác không hoàn tất và trạng thái Module chưa được xác nhận thay đổi.',
                'guidance' => 'Vui lòng kiểm tra log hệ thống. Chạy lại preflight trước khi thử lại.',
            ];
        }
    }

    public function closeLifecycleModal(): void
    {
        $this->lifecycleModalOpen = false;
        $this->lifecycleModule = null;
        $this->lifecyclePreflight = [];
        $this->lifecycleResult = null;
    }

    public function render()
    {
        return view('System::livewire.settings.modules-form');
    }
}
