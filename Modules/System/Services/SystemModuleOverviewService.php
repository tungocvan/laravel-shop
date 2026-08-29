<?php

namespace Modules\System\Services;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemModuleOverviewService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleLifecycleManager $lifecycle,
    ) {}

    public function rows(): array
    {
        $modules = $this->registry->current();

        return $modules
            ->map(function (array $module) use ($modules): array {
                $usedBy = $modules
                    ->filter(fn (array $candidate): bool => $candidate['enabled']
                        && in_array($module['name'], $candidate['depends'], true))
                    ->pluck('name')
                    ->values()
                    ->all();

                try {
                    $database = $this->lifecycle->databaseStatus($module);
                } catch (Throwable $e) {
                    Log::warning('Module database status check failed.', [
                        'module' => $module['name'],
                        'exception' => $e::class,
                    ]);
                    $database = ['tables' => [], 'missing_tables' => [], 'ready' => false, 'error' => true];
                }

                return [
                    'name' => $module['name'],
                    'type' => $module['type'] ?? 'feature',
                    'enabled' => (bool) ($module['enabled'] ?? false),
                    'required' => (bool) ($module['required'] ?? false),
                    'depends' => $module['depends'],
                    'used_by' => $usedBy,
                    'path' => $module['path'] ?? '',
                    'source' => $module['source'] ?? '',
                    'database' => $database,
                ];
            })
            ->sortBy(fn (array $module): string => $module['type'].'|'.$module['name'])
            ->values()
            ->all();
    }
}
