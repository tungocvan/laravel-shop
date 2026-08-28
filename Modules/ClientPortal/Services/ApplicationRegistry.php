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

        $features = $this->normalizeFeatures((array) ($manifest['features'] ?? []));
        $navigation = $this->normalizeNavigation((array) ($manifest['navigation'] ?? []));
        if ($navigation === []) {
            $navigation = collect($features)
                ->filter(fn (array $feature): bool => ! empty($feature['route']))
                ->map(fn (array $feature): array => [
                    'key' => $feature['key'],
                    'name' => $feature['name'],
                    'route' => $feature['route'],
                    'permission' => $feature['permission'],
                    'permissions' => array_values(array_filter([$feature['permission']])),
                    'icon' => $feature['icon'],
                    'sort_order' => $feature['sort_order'],
                    'placement' => 'primary',
                ])
                ->values()
                ->all();
        }

        return [
            'key' => $key,
            'module' => $sourceModule,
            'name' => $name,
            'description' => trim((string) ($manifest['description'] ?? '')),
            'icon' => trim((string) ($manifest['icon'] ?? 'squares-2x2')),
            'route' => $route,
            'permission' => $this->nullableString($manifest['permission'] ?? null),
            'sort_order' => (int) ($manifest['sort_order'] ?? 100),
            'layout' => $this->normalizeLayout((array) ($manifest['layout'] ?? [])),
            'capabilities' => $this->normalizeCapabilities((array) ($manifest['capabilities'] ?? [])),
            'quick_actions' => $this->normalizeQuickActions((array) ($manifest['quick_actions'] ?? [])),
            'navigation' => $navigation,
            'features' => $features,
        ];
    }

    private function normalizeFeatures(array $features): array
    {
        return collect($features)
            ->filter(fn (mixed $feature): bool => is_array($feature))
            ->map(function (array $feature, string $featureKey): array {
                $actions = collect($feature['actions'] ?? [])
                    ->filter(fn (mixed $action): bool => is_array($action))
                    ->map(fn (array $action, string $actionKey): array => [
                        'key' => Str::lower(trim((string) ($action['key'] ?? $actionKey))),
                        'name' => trim((string) ($action['name'] ?? Str::headline($actionKey))),
                        'permission' => $this->nullableString($action['permission'] ?? null),
                        'sort_order' => (int) ($action['sort_order'] ?? 100),
                    ])
                    ->sortBy(fn (array $action): array => [$action['sort_order'], $action['name']])
                    ->values()
                    ->all();

                return [
                    'key' => Str::lower(trim((string) ($feature['key'] ?? $featureKey))),
                    'name' => trim((string) ($feature['name'] ?? Str::headline($featureKey))),
                    'description' => trim((string) ($feature['description'] ?? '')),
                    'route' => $this->nullableString($feature['route'] ?? null),
                    'permission' => $this->nullableString($feature['permission'] ?? null),
                    'icon' => trim((string) ($feature['icon'] ?? 'square-3-stack-3d')),
                    'sort_order' => (int) ($feature['sort_order'] ?? 100),
                    'actions' => $actions,
                ];
            })
            ->sortBy(fn (array $feature): array => [$feature['sort_order'], $feature['name']])
            ->values()
            ->all();
    }

    private function normalizeNavigation(array $navigation): array
    {
        return collect($navigation)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item, string|int $itemKey): array {
                $fallbackKey = is_string($itemKey) ? $itemKey : ($item['name'] ?? 'item-'.$itemKey);
                $placement = $item['placement'] ?? 'primary';
                $permission = $this->nullableString($item['permission'] ?? null);
                $permissions = $this->normalizePermissions((array) ($item['permissions'] ?? []), $permission);

                return [
                    'key' => Str::lower(trim((string) ($item['key'] ?? $fallbackKey))),
                    'name' => trim((string) ($item['name'] ?? Str::headline((string) $fallbackKey))),
                    'route' => $this->nullableString($item['route'] ?? null),
                    'permission' => $permission,
                    'permissions' => $permissions,
                    'icon' => trim((string) ($item['icon'] ?? 'square-3-stack-3d')),
                    'sort_order' => (int) ($item['sort_order'] ?? 100),
                    'placement' => in_array($placement, ['primary', 'more'], true) ? $placement : 'primary',
                ];
            })
            ->filter(fn (array $item): bool => $item['key'] !== '' && $item['name'] !== '' && $item['route'] !== null)
            ->sortBy(fn (array $item): array => [$item['sort_order'], $item['name']])
            ->values()
            ->all();
    }

    private function normalizeQuickActions(array $actions): array
    {
        return collect($actions)
            ->filter(fn (mixed $action): bool => is_array($action))
            ->map(function (array $action, string|int $actionKey): array {
                $fallbackKey = is_string($actionKey) ? $actionKey : ($action['name'] ?? 'action-'.$actionKey);
                $permission = $this->nullableString($action['permission'] ?? null);

                return [
                    'key' => Str::lower(trim((string) ($action['key'] ?? $fallbackKey))),
                    'name' => trim((string) ($action['name'] ?? Str::headline((string) $fallbackKey))),
                    'route' => $this->nullableString($action['route'] ?? null),
                    'permission' => $permission,
                    'permissions' => $this->normalizePermissions((array) ($action['permissions'] ?? []), $permission),
                    'icon' => trim((string) ($action['icon'] ?? 'bolt')),
                    'sort_order' => (int) ($action['sort_order'] ?? 100),
                ];
            })
            ->filter(fn (array $action): bool => $action['key'] !== '' && $action['name'] !== '' && $action['route'] !== null)
            ->sortBy(fn (array $action): array => [$action['sort_order'], $action['name']])
            ->values()
            ->all();
    }

    private function normalizePermissions(array $permissions, ?string $permission = null): array
    {
        return collect($permissions)
            ->push($permission)
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeCapabilities(array $capabilities): array
    {
        return collect($capabilities)
            ->filter(fn (mixed $capability): bool => is_string($capability) && trim($capability) !== '')
            ->map(fn (string $capability): string => Str::lower(trim($capability)))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeLayout(array $layout): array
    {
        $mode = trim((string) ($layout['mode'] ?? 'standard'));

        return [
            'mode' => in_array($mode, ['standard', 'workspace', 'focus', 'full-width'], true) ? $mode : 'standard',
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
