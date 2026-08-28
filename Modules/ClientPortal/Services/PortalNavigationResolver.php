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
            ->filter(fn (array $item): bool => $this->allows($user, $item))
            ->values();
    }

    public function quickActionsFor(array $application, ?User $user): Collection
    {
        return collect($application['quick_actions'] ?? [])
            ->filter(fn (array $action): bool => $this->allows($user, $action))
            ->values();
    }

    private function allows(?User $user, array $item): bool
    {
        $permissions = collect($item['permissions'] ?? [])
            ->filter(fn (mixed $permission): bool => is_string($permission) && trim($permission) !== '')
            ->values();

        if ($permissions->isEmpty()) {
            return $this->access->can($user, $item['permission'] ?? null);
        }

        return $permissions->every(fn (string $permission): bool => $this->access->can($user, $permission));
    }
}
