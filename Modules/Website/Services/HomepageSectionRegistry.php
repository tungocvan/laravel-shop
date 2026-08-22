<?php

namespace Modules\Website\Services;

use InvalidArgumentException;

class HomepageSectionRegistry
{
    public function all(): array
    {
        return (array) config('website.homepage.sections', []);
    }

    public function canonicalKey(string $sectionKey): string
    {
        return (string) preg_replace('/_copy_\d+$/', '', $sectionKey);
    }

    public function get(string $sectionKey): ?array
    {
        $definition = $this->all()[$this->canonicalKey($sectionKey)] ?? null;

        return is_array($definition) ? $definition : null;
    }

    public function resolve(string $sectionKey, ?string $storedType = null): array
    {
        $canonicalKey = $this->canonicalKey($sectionKey);
        $definition = $this->get($sectionKey);
        if ($definition === null) {
            throw new InvalidArgumentException("Unknown homepage section key: {$sectionKey}");
        }

        $renderer = $definition['renderer'] ?? null;
        if (! is_string($renderer) || $renderer === '') {
            throw new InvalidArgumentException("Homepage section {$sectionKey} has no renderer");
        }

        $configuredType = (string) ($definition['type'] ?? $canonicalKey);
        $acceptedTypes = array_values(array_unique([$configuredType, $canonicalKey]));
        if ($storedType !== null && $storedType !== '' && ! in_array($storedType, $acceptedTypes, true)) {
            throw new InvalidArgumentException(
                "Homepage section {$sectionKey} expects type {$configuredType}, got {$storedType}"
            );
        }

        return $definition + [
            'key' => $canonicalKey,
            'type' => $configuredType,
            'label' => str($canonicalKey)->replace('_', ' ')->title()->toString(),
            'description' => 'Homepage section',
            'params' => [],
            'props' => [],
            'duplicatable' => false,
            'admin' => null,
        ];
    }

    public function adminAction(string $sectionKey): ?array
    {
        $admin = $this->resolve($sectionKey)['admin'] ?? null;
        if (! is_array($admin)) {
            return null;
        }

        $label = trim((string) ($admin['label'] ?? 'Quản trị component'));

        if (isset($admin['route']) && is_string($admin['route']) && $admin['route'] !== '') {
            if (! \Illuminate\Support\Facades\Route::has($admin['route'])) {
                return null;
            }

            return [
                'type' => 'route',
                'label' => $label,
                'route' => $admin['route'],
                'url' => route($admin['route']),
            ];
        }

        if (isset($admin['tab']) && is_string($admin['tab']) && $admin['tab'] !== '') {
            return [
                'type' => 'tab',
                'label' => $label,
                'tab' => $admin['tab'],
            ];
        }

        return null;
    }

    public function paramsFor(array $definition, array $context = []): array
    {
        $params = is_array($definition['params'] ?? null) ? $definition['params'] : [];

        foreach ((array) ($definition['props'] ?? []) as $param => $contextKey) {
            if (is_string($param) && is_string($contextKey) && array_key_exists($contextKey, $context)) {
                $params[$param] = $context[$contextKey];
            }
        }

        return $params;
    }
}
