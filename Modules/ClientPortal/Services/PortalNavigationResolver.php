<?php

namespace Modules\ClientPortal\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class PortalNavigationResolver
{
    public function __construct(private readonly PortalAccessResolver $access, private readonly ClientPortalSettingsService $settings)
    {
    }

    public function forApplication(array $application, ?User $user): Collection
    {
        $presentation = collect($this->settings->applicationNavigationPresentation($application)['items'])->keyBy('key');

        return collect($application['navigation'] ?? [])
            ->filter(fn (array $item): bool => $this->access->can($user, $item['permission'] ?? null))
            ->map(function (array $item) use ($presentation): array {
                $override = (array) $presentation->get($item['key'], []);
                return array_replace($item, [
                    'bottom_enabled' => (bool) ($override['bottom_enabled'] ?? true),
                    'bottom_sort_order' => (int) ($override['bottom_sort_order'] ?? ($item['sort_order'] ?? 100)),
                    'bottom_icon' => trim((string) ($override['bottom_icon'] ?? ($item['icon'] ?? 'squares-2x2'))),
                    'bottom_label' => trim((string) ($override['bottom_label'] ?? ($item['name'] ?? ''))),
                ]);
            })
            ->values();
    }

    public function quickActionsFor(array $application, ?User $user): Collection
    {
        return collect($application['quick_actions'] ?? [])
            ->filter(fn (array $action): bool => $this->access->can($user, $action['permission'] ?? null))
            ->values();
    }
}
