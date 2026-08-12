<?php

namespace Modules\System\Livewire\Settings;

use App\Modules\ModuleLifecycleManager;
use App\Services\RealtimeManager;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use LogicException;
use Modules\Admin\Services\ModuleRouteManager;
use Modules\System\Livewire\Concerns\AuthorizesSystemActions;
use Modules\System\Services\SystemModuleControlService;
use Throwable;

class ModulesForm extends Component
{
    use AuthorizesSystemActions;

    public array $modules = [];
    public bool $realtimeEnabled = false;
    public array $realtimeStatus = [];
    public array $moduleRoutes = [];
    public ?string $editingRouteKey = null;
    public string $routeTitle = '';
    public string $routeSearch = '';
    public string $routeModuleFilter = '';
    public bool $canUpdate = false;

    public function mount(): void
    {
        $this->canUpdate = (bool) (auth('admin')->user()?->can('system.modules.update'));
        $this->loadModules();
        $this->refreshRealtimeStatus();
        $this->loadModuleRoutes();
    }

    public function toggleRealtime(RealtimeManager $realtime, SystemModuleControlService $control): void
    {
        $this->authorizePermission('system.modules.update');

        try {
            $control->toggleRealtime($realtime, $this->realtimeEnabled, auth('admin')->id());
            $this->refreshRealtimeStatus();
            session()->flash('message', 'Realtime Socket.IO đã được '.($this->realtimeEnabled ? 'bật' : 'tắt').'. Không cần build lại frontend.');
        } catch (Throwable $e) {
            Log::warning('ModulesForm realtime mutation failed.', ['exception' => $e::class]);
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
            Log::warning('ModulesForm realtime health check failed.', ['exception' => $e::class]);
            $this->realtimeStatus = ['ok' => false];
        }
    }

    public function loadModules(): void
    {
        $registry = config('modules.registry', []);
        $lifecycle = app(ModuleLifecycleManager::class);

        $this->modules = collect(is_array($registry) ? $registry : [])
            ->map(function (array $module, string $name) use ($registry, $lifecycle): array {
                $usedBy = collect($registry)
                    ->filter(fn (array $candidate): bool => (bool) ($candidate['enabled'] ?? false)
                        && in_array($name, $candidate['depends'] ?? [], true))
                    ->keys()
                    ->values()
                    ->all();

                try {
                    $database = $lifecycle->databaseStatus($module + ['name' => $name]);
                } catch (Throwable $e) {
                    Log::warning('Module database status check failed.', [
                        'module' => $name,
                        'exception' => $e::class,
                    ]);
                    $database = ['tables' => [], 'missing_tables' => [], 'ready' => false, 'error' => true];
                }

                return [
                    'name' => $name,
                    'type' => $module['type'] ?? 'feature',
                    'enabled' => (bool) ($module['enabled'] ?? false),
                    'required' => (bool) ($module['required'] ?? (($module['type'] ?? null) === 'shell')),
                    'depends' => $module['depends'] ?? [],
                    'used_by' => $usedBy,
                    'path' => $module['path'] ?? '',
                    'source' => $module['source'] ?? '',
                    'database' => $database,
                ];
            })
            ->sortBy(fn (array $module): string => ($module['type'] ?? '').'|'.$module['name'])
            ->values()
            ->all();
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
            session()->flash('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::warning('ModulesForm module toggle failed.', [
                'module' => $moduleName,
                'exception' => $e::class,
            ]);
            session()->flash('error', "Không thể cập nhật module {$moduleName}. Vui lòng kiểm tra log hệ thống.");
        }
    }

    public function deleteModule(string $moduleName, SystemModuleControlService $control): void
    {
        $this->authorizePermission('system.modules.update');

        try {
            $result = $control->archive($moduleName, auth('admin')->id());
            $this->loadModules();
            session()->flash('message', "Đã lưu trữ module {$moduleName}. Bản phục hồi: {$result['archive']}. Database được giữ nguyên.");
        } catch (LogicException $e) {
            session()->flash('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::warning('ModulesForm module archive failed.', [
                'module' => $moduleName,
                'exception' => $e::class,
            ]);
            session()->flash('error', "Không thể lưu trữ module {$moduleName}. Vui lòng kiểm tra log hệ thống.");
        }
    }

    public function loadModuleRoutes(): void
    {
        $this->moduleRoutes = app(ModuleRouteManager::class)->rows();
    }

    public function getFilteredModuleRoutesProperty(): array
    {
        $search = mb_strtolower(trim($this->routeSearch));

        return collect($this->moduleRoutes)
            ->filter(function (array $route) use ($search): bool {
                if ($this->routeModuleFilter !== '' && $route['module'] !== $this->routeModuleFilter) {
                    return false;
                }

                if ($search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $route['module'],
                    $route['name'] ?? '',
                    $route['uri'],
                    $route['title'],
                    $route['permission'] ?? '',
                ]));

                return str_contains($haystack, $search);
            })
            ->values()
            ->all();
    }

    public function editRouteTitle(string $key): void
    {
        $row = collect($this->moduleRoutes)->firstWhere('key', $key);
        if (! $row) {
            return;
        }

        $this->editingRouteKey = $key;
        $this->routeTitle = (string) $row['title'];
    }

    public function saveRouteTitle(ModuleRouteManager $routes): void
    {
        $this->authorizePermission('system.modules.update');
        $this->validate([
            'routeTitle' => ['required', 'string', 'max:255'],
        ]);

        $row = collect($this->moduleRoutes)->firstWhere('key', $this->editingRouteKey);
        if (! $row) {
            session()->flash('error', 'Route Module không còn tồn tại.');
            return;
        }

        try {
            $routes->saveTitle($row, $this->routeTitle);
            $this->editingRouteKey = null;
            $this->routeTitle = '';
            $this->loadModuleRoutes();
            session()->flash('message', 'Đã cập nhật Title Module.');
        } catch (Throwable $e) {
            Log::warning('ModulesForm route title update failed.', ['exception' => $e::class]);
            session()->flash('error', 'Không thể cập nhật Title Module. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function addRouteToMenu(string $key, ModuleRouteManager $routes): void
    {
        $this->authorizePermission('system.modules.update');

        $row = collect($this->moduleRoutes)->firstWhere('key', $key);
        if (! $row) {
            session()->flash('error', 'Route Module không còn tồn tại.');
            return;
        }

        try {
            $routes->addMenu($row);
            $this->loadModuleRoutes();
            session()->flash('message', "Đã thêm {$row['url']} vào menu.");
        } catch (LogicException $e) {
            session()->flash('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::warning('ModulesForm add route to menu failed.', ['exception' => $e::class]);
            session()->flash('error', 'Không thể thêm route vào menu. Vui lòng kiểm tra log hệ thống.');
        }
    }

    public function render()
    {
        return view('System::livewire.settings.modules-form');
    }
}
