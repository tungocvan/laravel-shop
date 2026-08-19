<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class ClientApplicationRegistry
{
    public function all(): Collection
    {
        return collect(config('modules.registry', []))
            ->filter(fn (array $module): bool => (bool) ($module['enabled'] ?? false))
            ->map(function (array $module, string $moduleName): ?array {
                $manifest = $this->loadManifest($moduleName, $module);

                return $manifest === null
                    ? null
                    : $this->normalizeManifest($moduleName, $manifest);
            })
            ->filter()
            ->sortBy(fn (array $application): array => [
                $application['sort_order'],
                $application['name'],
            ])
            ->values();
    }

    public function find(string $key): ?array
    {
        $normalizedKey = Str::lower(trim($key));

        return $this->all()->first(
            fn (array $application): bool => $application['key'] === $normalizedKey
        );
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

    private function loadManifest(string $moduleName, array $module): ?array
    {
        $modulePath = $module['path'] ?? base_path('Modules/'.$moduleName);
        $candidates = [
            $modulePath.'/config/client-app.php',
            $modulePath.'/Config/client-app.php',
        ];

        foreach ($candidates as $path) {
            if (! File::exists($path)) {
                continue;
            }

            $manifest = require $path;

            if (! is_array($manifest)) {
                throw new InvalidArgumentException("Client application manifest [{$path}] must return an array.");
            }

            return $manifest;
        }

        return null;
    }

    private function normalizeManifest(string $moduleName, array $manifest): array
    {
        $key = Str::lower(trim((string) ($manifest['key'] ?? $moduleName)));
        $name = trim((string) ($manifest['name'] ?? $moduleName));
        $route = trim((string) ($manifest['route'] ?? ''));

        if ($key === '' || $name === '' || $route === '') {
            throw new InvalidArgumentException("Client application manifest [{$moduleName}] requires key, name and route.");
        }

        $features = collect($manifest['features'] ?? [])
            ->filter(fn (mixed $feature): bool => is_array($feature))
            ->map(function (array $feature, string $featureKey): array {
                return [
                    'key' => Str::lower(trim((string) ($feature['key'] ?? $featureKey))),
                    'name' => trim((string) ($feature['name'] ?? Str::headline($featureKey))),
                    'description' => trim((string) ($feature['description'] ?? '')),
                    'route' => isset($feature['route']) ? trim((string) $feature['route']) : null,
                    'permission' => isset($feature['permission']) ? trim((string) $feature['permission']) : null,
                    'icon' => trim((string) ($feature['icon'] ?? 'square-3-stack-3d')),
                    'sort_order' => (int) ($feature['sort_order'] ?? 100),
                ];
            })
            ->sortBy(fn (array $feature): array => [$feature['sort_order'], $feature['name']])
            ->values()
            ->all();

        return [
            'key' => $key,
            'module' => $moduleName,
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
