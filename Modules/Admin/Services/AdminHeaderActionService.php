<?php

namespace Modules\Admin\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Admin\Support\AdminLayoutManager;

class AdminHeaderActionService
{
    private const ICONS = [
        'globe' => 'fa-solid fa-globe',
        'book' => 'fa-solid fa-book-open',
        'help' => 'fa-regular fa-circle-question',
        'link' => 'fa-solid fa-arrow-up-right-from-square',
        'message' => 'fa-regular fa-message',
        'calendar' => 'fa-regular fa-calendar',
        'star' => 'fa-regular fa-star',
    ];

    public function __construct(private readonly AdminLayoutManager $layoutManager)
    {
    }

    public function context(): array
    {
        $header = (array) data_get($this->layoutManager->config(), 'header', []);
        $user = Auth::guard('admin')->user();
        $items = $this->customItems((array) data_get($header, 'actions.items', []), $user);

        return [
            'notifications' => (bool) data_get($header, 'notifications', true),
            'items' => $items,
            'mobile_overflow' => (bool) data_get($header, 'actions.mobile_overflow', true),
            'overflow_secondary_actions' => (bool) data_get($header, 'responsive.overflow_secondary_actions', true),
        ];
    }

    private function customItems(array $items, ?Authenticatable $user): array
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

    private function normalizeItem(array $item): ?array
    {
        $title = trim((string) data_get($item, 'title', ''));
        $url = $this->safeUrl(data_get($item, 'url'));
        $icon = $this->safeIcon(data_get($item, 'icon'));

        if ($title === '' || $url === null || $icon === null) {
            return null;
        }

        return [
            'title' => mb_substr($title, 0, 80),
            'url' => $url,
            'icon' => $icon,
            'target' => data_get($item, 'target') === '_blank' ? '_blank' : '_self',
            'priority' => data_get($item, 'priority') === 'primary' ? 'primary' : 'secondary',
            'badge' => $this->badge(data_get($item, 'badge')),
        ];
    }

    private function allowed(array $item, ?Authenticatable $user): bool
    {
        $permission = trim((string) data_get($item, 'permission', ''));

        return $permission === '' || ($user !== null && Gate::forUser($user)->allows($permission));
    }

    private function safeUrl(mixed $value): ?string
    {
        $url = is_string($value) ? trim($value) : '';

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return mb_substr($url, 0, 255);
        }

        if (filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return mb_substr($url, 0, 255);
        }

        return null;
    }

    private function safeIcon(mixed $value): ?string
    {
        $icon = is_string($value) ? trim($value) : '';

        if (array_key_exists($icon, self::ICONS)) {
            return self::ICONS[$icon];
        }

        return in_array($icon, self::ICONS, true) ? $icon : null;
    }

    private function badge(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $badge = trim((string) $value);

        return $badge === '' ? null : mb_substr($badge, 0, 4);
    }
}
