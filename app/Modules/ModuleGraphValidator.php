<?php

namespace App\Modules;

use Illuminate\Support\Collection;
use LogicException;

class ModuleGraphValidator
{
    public function validate(Collection $modules): void
    {
        $registry = $modules->keyBy('name');

        foreach ($modules as $module) {
            if ($module['required'] && ! $module['enabled']) {
                throw new LogicException("Shell module [{$module['name']}] is required and cannot be disabled.");
            }

            if (! $module['enabled']) {
                continue;
            }

            foreach ($module['depends'] as $dependency) {
                if (! $registry->has($dependency)) {
                    throw new LogicException("Module [{$module['name']}] requires missing module [{$dependency}].");
                }

                if (! $registry->get($dependency)['enabled']) {
                    throw new LogicException("Module [{$module['name']}] requires disabled module [{$dependency}].");
                }

                if ($dependency === $module['name']) {
                    throw new LogicException("Module [{$module['name']}] cannot depend on itself.");
                }
            }
        }

        $visiting = [];
        $visited = [];
        $visit = function (string $name) use (&$visit, &$visiting, &$visited, $registry): void {
            if (isset($visited[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                throw new LogicException("Circular module dependency detected at [{$name}].");
            }

            $visiting[$name] = true;
            foreach ($registry->get($name)['depends'] as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$name]);
            $visited[$name] = true;
        };

        foreach ($modules->where('enabled', true)->pluck('name') as $name) {
            $visit($name);
        }
    }

    public function withState(Collection $modules, string $moduleName, bool $enabled): Collection
    {
        if (! $modules->contains('name', $moduleName)) {
            throw new LogicException('Module không tồn tại trong catalog.');
        }

        $updated = $modules
            ->map(function (array $module) use ($moduleName, $enabled): array {
                if ($module['name'] !== $moduleName) {
                    return $module;
                }

                return array_replace($module, [
                    'enabled' => $enabled,
                    'source' => 'runtime',
                ]);
            })
            ->values();

        $this->validate($updated);

        return $updated;
    }
}
