<?php

namespace Modules\Website\Services;

class HeaderLayoutService
{
    public function __construct(private readonly HeaderComponentRegistry $registry)
    {
    }

    public function resolvedLayout(?array $layout = null): array
    {
        $layout ??= (array) config('website.header.layout', []);

        return [
            'desktop.topbar' => $this->resolveSlot('desktop.topbar', data_get($layout, 'desktop.topbar', [])),
            'desktop.main.left' => $this->resolveSlot('desktop.main.left', data_get($layout, 'desktop.main.left', [])),
            'desktop.main.center' => $this->resolveSlot('desktop.main.center', data_get($layout, 'desktop.main.center', [])),
            'desktop.main.right' => $this->resolveSlot('desktop.main.right', data_get($layout, 'desktop.main.right', [])),
            'mobile.search' => $this->resolveSlot('mobile.search', data_get($layout, 'mobile.search', [])),
            'mobile.drawer' => $this->resolveSlot('mobile.drawer', data_get($layout, 'mobile.drawer', [])),
        ];
    }

    private function resolveSlot(string $slot, mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $resolved = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['type'] ?? null)) {
                continue;
            }

            try {
                $definition = $this->registry->resolve($item['type'], $slot);
            } catch (\InvalidArgumentException) {
                continue;
            }

            $resolved[] = [
                'type' => $item['type'],
                'view' => $definition['view'],
                'config' => is_array($item['config'] ?? null) ? $item['config'] : [],
            ];
        }

        return $resolved;
    }
}
