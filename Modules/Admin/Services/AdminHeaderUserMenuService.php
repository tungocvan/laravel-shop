<?php

namespace Modules\Admin\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Modules\Admin\Support\AdminLayoutManager;
use Modules\Website\Services\HeaderMenuService;

class AdminHeaderUserMenuService
{
    private const ICONS = [
        'user' => 'fa-solid fa-user',
        'gear' => 'fa-solid fa-gear',
        'lock' => 'fa-solid fa-lock',
        'key' => 'fa-solid fa-key',
        'shield' => 'fa-solid fa-shield-halved',
        'link' => 'fa-solid fa-link',
    ];

    public function __construct(
        private readonly AdminLayoutManager $layoutManager,
        private readonly HeaderMenuService $headerMenuService,
    ) {
    }

    public function context(?Authenticatable $user): array
    {
        $config = (array) data_get($this->layoutManager->config(), 'header.user_menu_config', []);
        $configured = $this->configuredItems((array) data_get($config, 'items', []), $user);
        $legacy = $configured === [] ? $this->legacyItems($user) : [];

        return [
            'show_avatar' => (bool) data_get($config, 'show_avatar', true),
            'show_name' => (bool) data_get($config, 'show_name', true),
            'show_email' => (bool) data_get($config, 'show_email', true),
            'show_role' => (bool) data_get($config, 'show_role', false),
            'role' => $this->roleLabel($user),
            'items' => $configured !== [] ? $configured : $legacy,
        ];
    }

    private function configuredItems(array $items, ?Authenticatable $user): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && (bool) data_get($item, 'enabled', true))
            ->filter(fn ($item) => $this->allowed($item, $user))
            ->sortBy(fn ($item) => (int) data_get($item, 'order', 0))
            ->map(fn ($item) => $this->normalizeItem($item))
            ->filter()
            ->values()
            ->all();
    }

    private function legacyItems(?Authenticatable $user): array
    {
        $items = $this->headerMenuService->getMenuTreeByLocation('admin');

        if ($items->isEmpty()) {
            return [[
                'label' => 'Profile',
                'url' => route('admin.profile'),
                'icon' => self::ICONS['user'],
                'target' => '_self',
            ]];
        }

        return $items->map(function ($item) {
            return [
                'label' => (string) $item->title,
                'url' => $this->safeUrl($item->url),
                'icon' => $this->safeIcon($item->icon),
                'target' => $item->target === '_blank' ? '_blank' : '_self',
            ];
        })->filter(fn ($item) => $item['label'] !== '' && $item['url'] !== null)->values()->all();
    }

    private function normalizeItem(array $item): ?array
    {
        $label = trim((string) data_get($item, 'label', ''));
        $url = $this->safeUrl(data_get($item, 'url'));

        if ($label === '' || $url === null) {
            return null;
        }

        return [
            'label' => mb_substr($label, 0, 80),
            'url' => $url,
            'icon' => $this->safeIcon(data_get($item, 'icon')),
            'target' => data_get($item, 'target') === '_blank' ? '_blank' : '_self',
        ];
    }

    private function allowed(array $item, ?Authenticatable $user): bool
    {
        $permission = trim((string) data_get($item, 'permission', ''));

        if ($permission === '') {
            return true;
        }

        return $user !== null && Gate::forUser($user)->allows($permission);
    }

    private function safeUrl(mixed $value): ?string
    {
        $url = is_string($value) ? trim($value) : '';

        if ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        return mb_substr($url, 0, 255);
    }

    private function safeIcon(mixed $value): ?string
    {
        $icon = is_string($value) ? trim($value) : '';

        if (array_key_exists($icon, self::ICONS)) {
            return self::ICONS[$icon];
        }

        return in_array($icon, self::ICONS, true) ? $icon : null;
    }

    private function roleLabel(?Authenticatable $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (method_exists($user, 'getRoleNames')) {
            $role = $user->getRoleNames()->first();

            return is_string($role) && $role !== '' ? $role : null;
        }

        $role = data_get($user, 'role.name') ?? data_get($user, 'role');

        return is_string($role) && trim($role) !== '' ? mb_substr(trim($role), 0, 80) : null;
    }
}
