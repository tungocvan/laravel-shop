<?php

namespace Modules\ClientPortal\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ApplicationRegistry
{
    public function all(): Collection
    {
        $applicationsPath = base_path('Modules/ClientPortal/Applications');

        if (! File::isDirectory($applicationsPath)) {
            return collect();
        }

        return collect(File::directories($applicationsPath))
            ->map(function (string $path): ?array {
                $manifestPath = $path.'/manifest.php';
                if (! File::exists($manifestPath)) {
                    return null;
                }

                $manifest = require $manifestPath;
                if (! is_array($manifest)) {
                    throw new InvalidArgumentException("Client application manifest [{$manifestPath}] must return an array.");
                }

                $sourceModule = Str::studly((string) ($manifest['source_module'] ?? basename($path)));
                $source = config('modules.registry.'.$sourceModule);
                if (! is_array($source) || ! ($source['enabled'] ?? false)) {
                    return null;
                }

                return $this->normalizeManifest($sourceModule, $manifest);
            })
            ->filter()
            ->sortBy(fn (array $application): array => [$application['sort_order'], $application['name']])
            ->values();
    }

    public function find(string $key): ?array
    {
        $normalizedKey = Str::lower(trim($key));

        return $this->all()->first(fn (array $application): bool => $application['key'] === $normalizedKey);
    }

    public function forUser(?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        return $this->all()->filter(function (array $application) use ($user): bool {
            $permission = $application['permission'];

            return $permission === null || $this->userCan($user, $permission);
        })->values();
    }

    public function userCan(User $user, string $permission): bool
    {
        try {
            return $user->can($permission);
        } catch (Throwable) {
            return false;
        }
    }

    private function normalizeManifest(string $sourceModule, array $manifest): array
    {
        $key = Str::lower(trim((string) ($manifest['key'] ?? $sourceModule)));
        $name = trim((string) ($manifest['name'] ?? $sourceModule));
        $route = trim((string) ($manifest['route'] ?? ''));

        if ($key === '' || $name === '' || $route === '') {
            throw new InvalidArgumentException("Client application manifest [{$sourceModule}] requires key, name and route.");
        }

        $features = collect($manifest['features'] ?? [])
            ->filter(fn (mixed $feature): bool => is_array($feature))
            ->map(function (array $feature, string $featureKey): array {
                $actions = collect($feature['actions'] ?? [])
                    ->filter(fn (mixed $action): bool => is_array($action))
                    ->map(function (array $action, string $actionKey): array {
                        return [
                            'key' => Str::lower(trim((string) ($action['key'] ?? $actionKey))),
                            'name' => trim((string) ($action['name'] ?? Str::headline($actionKey))),
                            'permission' => isset($action['permission']) ? trim((string) $action['permission']) : null,
                            'sort_order' => (int) ($action['sort_order'] ?? 100),
                        ];
                    })
                    ->sortBy(fn (array $action): array => [$action['sort_order'], $action['name']])
                    ->values()
                    ->all();

                return [
                    'key' => Str::lower(trim((string) ($feature['key'] ?? $featureKey))),
                    'name' => trim((string) ($feature['name'] ?? Str::headline($featureKey))),
                    'description' => trim((string) ($feature['description'] ?? '')),
                    'route' => isset($feature['route']) ? trim((string) $feature['route']) : null,
                    'permission' => isset($feature['permission']) ? trim((string) $feature['permission']) : null,
                    'icon' => trim((string) ($feature['icon'] ?? 'square-3-stack-3d')),
                    'sort_order' => (int) ($feature['sort_order'] ?? 100),
                    'actions' => $actions,
                ];
            })
            ->sortBy(fn (array $feature): array => [$feature['sort_order'], $feature['name']])
            ->values()
            ->all();

        return [
            'key' => $key,
            'module' => $sourceModule,
            'name' => $name,
            'description' => trim((string) ($manifest['description'] ?? '')),
            'icon' => trim((string) ($manifest['icon'] ?? 'squares-2x2')),
            'route' => $route,
            'permission' => isset($manifest['permission']) ? trim((string) $manifest['permission']) : null,
            'sort_order' => (int) ($manifest['sort_order'] ?? 100),
            'features' => $features,
        ];
    }
}
