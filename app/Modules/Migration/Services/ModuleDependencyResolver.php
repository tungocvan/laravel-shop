<?php

namespace App\Modules\Migration\Services;

class ModuleDependencyResolver
{
    public function resolve(string $module): array
    {
        $resolved = [];
        $visiting = [];

        $visit = function (string $name) use (&$visit, &$resolved, &$visiting): void {
            if (in_array($name, $resolved, true)) {
                return;
            }

            if (isset($visiting[$name])) {
                throw new \LogicException("Circular module dependency detected at [{$name}].");
            }

            $path = collect([
                base_path("Modules/{$name}/config/module.php"),
                base_path("Modules/{$name}/Config/module.php"),
            ])->first(fn (string $candidate): bool => is_file($candidate));

            if ($path === null) {
                throw new \LogicException("Module manifest not found for [{$name}].");
            }

            $visiting[$name] = true;
            $config = require $path;

            foreach ($config['depends'] ?? [] as $dependency) {
                $visit((string) $dependency);
            }

            unset($visiting[$name]);
            $resolved[] = $name;
        };

        $visit($module);

        return $resolved;
    }
}
