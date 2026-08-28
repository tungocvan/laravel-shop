<?php

namespace Modules\ClientPortal\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class PortalNavigationResolver
{
    public function __construct(private readonly PortalAccessResolver $access)
    {
    }

    public function forApplication(array $application, ?User $user): Collection
    {
        return collect($application['navigation'] ?? [])
            ->filter(fn (array $item): bool => $this->access->can($user, $item['permission'] ?? null))
            ->values();
    }

    public function quickActionsFor(array $application, ?User $user): Collection
    {
        return collect($application['quick_actions'] ?? [])
            ->filter(fn (array $action): bool => $this->access->can($user, $action['permission'] ?? null))
            ->values();
    }
}
