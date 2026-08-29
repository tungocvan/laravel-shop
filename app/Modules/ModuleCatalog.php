<?php

namespace App\Modules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleCatalog
{
    private const MODULE_TYPES = ['shell', 'domain', 'support'];

    private const BOOT_ORDER = [
        'shell' => 0,
        'support' => 1,
        'domain' => 2,
    ];

    private const TYPE_FALLBACKS = [
        'Admin' => 'shell',
        'Auth' => 'support',
        'Role' => 'support',
        'Template' => 'support',
    ];

    public function __construct(
        private readonly ModuleStateResolver $states,
    ) {}

    public function discover(?string $root = null): Collection
    {
        $root ??= base_path('Modules');

        if (! File::exists($root)) {
            return collect();
        }

        return collect(File::directories($root))
            ->map(fn (string $modulePath): array => $this->resolve($modulePath))
            ->sort(function (array $left, array $right): int {
                $leftOrder = self::BOOT_ORDER[$left['type']] ?? PHP_INT_MAX;
                $rightOrder = self::BOOT_ORDER[$right['type']] ?? PHP_INT_MAX;

                return $leftOrder === $rightOrder
                    ? strcmp($left['name'], $right['name'])
                    : $leftOrder <=> $rightOrder;
            })
            ->values();
    }

    public function resolve(string $modulePath): array
    {
        $module = basename($modulePath);
        $manifestPath = $this->manifestPath($modulePath);
        $manifest = [];
        $source = 'fallback';

        if ($manifestPath !== null) {
            $loaded = require $manifestPath;

            if (is_array($loaded)) {
                $manifest = $loaded;
                $source = 'manifest';
            }
        }

        $type = $manifest['type'] ?? $this->inferType($module);
        if (! in_array($type, self::MODULE_TYPES, true)) {
            $type = $this->inferType($module);
            $source = 'fallback';
        }

        $required = $type === 'shell';
        $state = $this->states->resolve($module, $manifest, $source, $required);

        return [
            'name' => $module,
            'path' => $modulePath,
            'lower_name' => Str::lower($module),
            'type' => $type,
            'enabled' => $state['enabled'],
            'required' => $required,
            'depends' => array_values(array_unique(array_map(
                static fn (mixed $dependency): string => Str::studly((string) $dependency),
                is_array($manifest['depends'] ?? null) ? $manifest['depends'] : []
            ))),
            'source' => $state['source'],
            'manifest_path' => $manifestPath,
            'manifest_exists' => $manifestPath !== null,
            'manifest' => $manifest,
            'default_enabled' => (bool) ($manifest['default_enabled'] ?? $manifest['enabled'] ?? true),
            'permissions_required' => (bool) ($manifest['permissions_required'] ?? true),
        ];
    }

    private function manifestPath(string $modulePath): ?string
    {
        return collect([$modulePath.'/config/module.php', $modulePath.'/Config/module.php'])
            ->first(fn (string $path): bool => File::exists($path));
    }

    private function inferType(string $module): string
    {
        return self::TYPE_FALLBACKS[$module] ?? 'domain';
    }
}
