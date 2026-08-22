<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\Route;
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
        ];
    }

    public function adminAction(string $sectionKey): ?array
    {
        $definition = $this->get($sectionKey);
        $admin = is_array($definition['admin'] ?? null) ? $definition['admin'] : null;
        if ($admin === null) {
            return null;
        }

        $label = trim((string) ($admin['label'] ?? 'Quản trị component'));
        $routeName = $admin['route'] ?? null;
        if (is_string($routeName) && $routeName !== '' && Route::has($routeName)) {
            return [
                'type' => 'route',
                'label' => $label,
                'route' => $routeName,
                'url' => route($routeName),
            ];
        }

        $tab = $admin['tab'] ?? null;
        if (is_string($tab) && $tab !== '') {
            return [
                'type' => 'tab',
                'label' => $label,
                'tab' => $tab,
            ];
        }

        return null;
    }

    public function adminCards(array $sectionOrder, array $sectionTypes = []): array
    {
        return collect($sectionOrder)->map(function (string $layoutKey) use ($sectionTypes): array {
            $sectionKey = str_starts_with($layoutKey, 'show_') ? substr($layoutKey, 5) : $layoutKey;
            $definition = $this->resolve($sectionKey, $sectionTypes[$sectionKey] ?? null);

            return [
                'layout_key' => $layoutKey,
                'section_key' => $sectionKey,
                'canonical_key' => $this->canonicalKey($sectionKey),
                'label' => (string) ($definition['label'] ?? $sectionKey),
                'description' => (string) ($definition['description'] ?? ''),
                'duplicatable' => (bool) ($definition['duplicatable'] ?? false),
                'is_copy' => $sectionKey !== $this->canonicalKey($sectionKey),
                'admin' => $this->adminAction($sectionKey),
            ];
        })->all();
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
