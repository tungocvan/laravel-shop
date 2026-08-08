<?php

namespace Modules\Admin\Livewire\Settings;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Services\RealtimeManager;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class ModulesForm extends Component
{
    public $modules = [];
    public bool $realtimeEnabled = false;
    public array $realtimeStatus = [];

    public function mount()
    {
        $this->loadModules();
        $this->refreshRealtimeStatus();
    }

    public function toggleRealtime(RealtimeManager $realtime): void
    {
        try {
            $realtime->setEnabled(! $this->realtimeEnabled);
            $this->refreshRealtimeStatus();
            session()->flash('message', 'Realtime Socket.IO đã được ' . ($this->realtimeEnabled ? 'bật' : 'tắt') . '. Không cần build lại frontend.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Không thể cập nhật realtime: ' . $e->getMessage());
        }
    }

    public function refreshRealtimeStatus(): void
    {
        $realtime = app(RealtimeManager::class);
        $this->realtimeEnabled = $realtime->enabled();
        $this->realtimeStatus = $realtime->health();
    }

    public function loadModules()
    {
        $registry = config('modules.registry', []);
        $lifecycle = app(ModuleLifecycleManager::class);
        $this->modules = collect($registry)->map(function ($module, $name) use ($registry, $lifecycle) {
            $usedBy = collect($registry)
                ->filter(fn ($candidate) => ($candidate['enabled'] ?? false)
                    && in_array($name, $candidate['depends'] ?? [], true))
                ->keys()
                ->values()
                ->all();

            try {
                $database = $lifecycle->databaseStatus($module);
            } catch (\Throwable $e) {
                $database = ['tables' => [], 'missing_tables' => [], 'ready' => false, 'error' => $e->getMessage()];
            }

            return [
                'name' => $name,
                'type' => $module['type'],
                'enabled' => $module['enabled'],
                'required' => $module['required'] ?? $module['type'] === 'shell',
                'depends' => $module['depends'] ?? [],
                'used_by' => $usedBy,
                'path' => $module['path'],
                'source' => $module['source'],
                'database' => $database,
            ];
        })->sortBy(['type', 'name'])->values()->all();
    }

    public function toggleModule($moduleName, ModuleLifecycleManager $lifecycle, ModulePermissionManager $permissions)
    {
        $module = collect($this->modules)->firstWhere('name', $moduleName);
        if (!$module) return;

        $newEnabled = !$module['enabled'];

        if (! $newEnabled && $module['required']) {
            session()->flash('error', "{$moduleName} là Shell Module bắt buộc và không thể tắt.");
            return;
        }

        if ($newEnabled) {
            $disabledDependencies = collect($module['depends'])
                ->filter(fn ($dependency) => ! collect($this->modules)->firstWhere('name', $dependency)['enabled'])
                ->values();
            if ($disabledDependencies->isNotEmpty()) {
                session()->flash('error', "Hãy bật module phụ thuộc trước: {$disabledDependencies->join(', ')}.");
                return;
            }

            try {
                $migration = $lifecycle->migrateIfNeeded($module);
            } catch (\Throwable $e) {
                session()->flash('error', "Không thể bật module {$moduleName}: {$e->getMessage()}");
                return;
            }
        } else {
            $dependents = collect($this->modules)
                ->filter(fn ($candidate) => $candidate['enabled'] && in_array($moduleName, $candidate['depends'], true))
                ->pluck('name')->values();
            if ($dependents->isNotEmpty()) {
                session()->flash('error', "Không thể tắt module {$moduleName} vì đang được sử dụng bởi: {$dependents->join(', ')}. Hãy tắt các module phụ thuộc trước.");
                return;
            }
        }

        try {
            $permissionCount = $newEnabled ? $permissions->sync($module) : 0;
        } catch (\Throwable $e) {
            session()->flash('error', "Không thể đồng bộ phân quyền module {$moduleName}: {$e->getMessage()}");
            return;
        }

        // Update manifest file
        $success = $this->updateModuleManifest($module['path'], $newEnabled);

        if ($success) {
            // Update in-memory config
            config(['modules.registry.' . $moduleName . '.enabled' => $newEnabled]);

            if (! $newEnabled) {
                $permissions->forgetCache();
            }

            // Reload modules
            $this->loadModules();

            $suffix = $newEnabled && ($migration['migrated'] ?? false) ? ' và đã migrate database' : '';
            $suffix .= $newEnabled && $permissionCount > 0 ? "; đã đồng bộ {$permissionCount} quyền" : '';
            session()->flash('message', 'Module ' . $moduleName . ' đã được ' . ($newEnabled ? 'bật' : 'tắt') . $suffix);
        }
        // If not successful, error message is already set in updateModuleManifest
    }

    public function deleteModule(string $moduleName, ModuleLifecycleManager $lifecycle): void
    {
        $module = collect($this->modules)->firstWhere('name', $moduleName);
        if (! $module) {
            return;
        }

        try {
            $registry = config('modules.registry', []);
            $destination = $lifecycle->archive($module, $registry);
            unset($registry[$moduleName]);
            config(['modules.registry' => $registry]);
            $this->loadModules();
            session()->flash('message', "Đã gỡ module {$moduleName}. Bản phục hồi: {$destination}. Database được giữ nguyên.");
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    private function updateModuleManifest($modulePath, $enabled)
    {
        $manifestPaths = [
            $modulePath . '/config/module.php',
            $modulePath . '/Config/module.php',
        ];

        foreach ($manifestPaths as $manifestPath) {
            if (File::exists($manifestPath)) {
                try {
                    // Check if file is writable
                    if (!File::isWritable($manifestPath)) {
                        throw new \Exception("File không có quyền ghi: {$manifestPath}");
                    }

                    $manifest = require $manifestPath;

                    if (is_array($manifest)) {
                        $manifest['enabled'] = $enabled;

                        $content = "<?php\n\nreturn " . var_export($manifest, true) . ";\n";
                        File::put($manifestPath, $content);
                        clearstatcache(true, $manifestPath);

                        if (function_exists('opcache_invalidate')) {
                            opcache_invalidate($manifestPath, true);
                        }
                    }
                } catch (\Exception $e) {
                    // Log error and show user-friendly message
                    \Log::error("Không thể cập nhật manifest module: " . $e->getMessage());
                    session()->flash('error', 'Không thể cập nhật module: ' . $e->getMessage());
                    return false;
                }
                break;
            }
        }

        return true;
    }

    public function render()
    {
        return view('Admin::livewire.settings.modules-form');
    }
}
