<?php

namespace Modules\Website\Services;

class FooterLayoutService
{
    public function __construct(private readonly FooterComponentRegistry $registry)
    {
    }

    public function resolvedLayout(?array $layout = null): array
    {
        $layout ??= (array) config('website.footer.layout', []);

        return [
            'desktop.top' => $this->resolveSlot('desktop.top', data_get($layout, 'desktop.top', [])),
            'desktop.main.brand' => $this->resolveSlot('desktop.main.brand', data_get($layout, 'desktop.main.brand', [])),
            'desktop.main.columns' => $this->resolveSlot('desktop.main.columns', data_get($layout, 'desktop.main.columns', [])),
            'desktop.main.extra' => $this->resolveSlot('desktop.main.extra', data_get($layout, 'desktop.main.extra', [])),
            'desktop.bottom.left' => $this->resolveSlot('desktop.bottom.left', data_get($layout, 'desktop.bottom.left', [])),
            'desktop.bottom.right' => $this->resolveSlot('desktop.bottom.right', data_get($layout, 'desktop.bottom.right', [])),
            'mobile.main' => $this->resolveSlot('mobile.main', data_get($layout, 'mobile.main', [])),
            'mobile.bottom' => $this->resolveSlot('mobile.bottom', data_get($layout, 'mobile.bottom', [])),
            'overlay' => $this->resolveSlot('overlay', data_get($layout, 'overlay', [])),
        ];
    }

    private function resolveSlot(string $slot, mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $resolved = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['type'] ?? null) || ($item['enabled'] ?? true) === false) {
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
