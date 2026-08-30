<?php

namespace Modules\Admin\Services;

/**
 * @deprecated Website owns public header-menu persistence and behavior.
 *
 * This compatibility adapter keeps the historical Admin service class
 * available while delegating all menu CRUD/tree operations to Website.
 */
class HeaderMenuService extends \Modules\Website\Services\HeaderMenuService
{
    public function exportAdminConfigItems(): array
    {
        return $this->getMenuTreeByLocation('admin')
            ->map(function ($item, int $index) {
                $url = is_string($item->url) ? trim($item->url) : '';

                if ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
                    return null;
                }

                return [
                    'enabled' => true,
                    'label' => mb_substr((string) $item->title, 0, 80),
                    'icon' => $this->configIcon($item->icon),
                    'url' => mb_substr($url, 0, 255),
                    'target' => $item->target === '_blank' ? '_blank' : '_self',
                    'permission' => null,
                    'order' => (int) ($item->sort_order ?? $index),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function configIcon(mixed $icon): string
    {
        $icon = is_string($icon) ? strtolower($icon) : '';

        return match (true) {
            str_contains($icon, 'user') => 'user',
            str_contains($icon, 'gear'), str_contains($icon, 'cog') => 'gear',
            str_contains($icon, 'lock') => 'lock',
            str_contains($icon, 'key') => 'key',
            str_contains($icon, 'shield') => 'shield',
            default => 'link',
        };
    }
}
