<?php

namespace App\Modules;

class ModuleStateResolver
{
    public function __construct(
        private readonly ModuleStateRepository $states,
    ) {}

    /**
     * @return array{enabled: bool, source: string}
     */
    public function resolve(string $module, array $manifest, string $manifestSource, bool $required): array
    {
        $defaultEnabled = (bool) ($manifest['default_enabled'] ?? $manifest['enabled'] ?? true);
        $runtimeEnabled = $this->states->get($module);

        if ($required) {
            return [
                'enabled' => true,
                'source' => $manifestSource,
            ];
        }

        return [
            'enabled' => $runtimeEnabled ?? $defaultEnabled,
            'source' => $runtimeEnabled !== null ? 'runtime' : $manifestSource,
        ];
    }
}
