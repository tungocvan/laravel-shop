<?php

namespace App\Modules;

use Illuminate\Support\Collection;

class ModuleRegistry
{
    public function __construct(
        private readonly ModuleCatalog $catalog,
        private readonly ModuleGraphValidator $validator,
    ) {}

    public function boot(): Collection
    {
        $modules = $this->fresh();
        $this->publish($modules);

        return $modules;
    }

    public function fresh(): Collection
    {
        $modules = $this->catalog->discover();
        $this->validator->validate($modules);

        return $modules;
    }

    public function publish(Collection $modules): array
    {
        $registry = $modules->mapWithKeys(fn (array $module): array => [
            $module['name'] => [
                'name' => $module['name'],
                'type' => $module['type'],
                'enabled' => $module['enabled'],
                'required' => $module['required'],
                'depends' => $module['depends'],
                'path' => $module['path'],
                'source' => $module['source'],
            ],
        ])->all();

        config(['modules.registry' => $registry]);

        return $registry;
    }

    public function current(): Collection
    {
        $registry = config('modules.registry', []);

        return collect(is_array($registry) ? $registry : [])
            ->map(function (array $module, string $name): array {
                return $module + [
                    'name' => $name,
                    'depends' => [],
                    'required' => ($module['type'] ?? null) === 'shell',
                ];
            })
            ->values();
    }
}
