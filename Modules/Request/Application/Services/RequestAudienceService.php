<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Models\RequestTypeAudience;
use Modules\Request\Models\RequestTypeVersion;
use Modules\Role\Contracts\RoleDirectory;
use Modules\User\Contracts\UserDirectory;

final class RequestAudienceService
{
    private array $versionCache = [];

    public function __construct(private readonly UserDirectory $users, private readonly RoleDirectory $roles) {}

    public function can(RequestTypeVersion $version, int $userId, AudienceCapability $capability): bool
    {
        return in_array($version->id, $this->eligibleVersionIds($userId, $capability), true);
    }

    public function eligibleVersionIds(int $userId, AudienceCapability $capability): array
    {
        $cacheKey = $userId.':'.$capability->value;
        if (array_key_exists($cacheKey, $this->versionCache)) {
            return $this->versionCache[$cacheKey];
        }

        if ($this->users->findActive($userId) === null) {
            return [];
        }

        $roleIds = $this->roles->activeAdminRoleIdsForUser($userId, 100);
        $capabilities = $capability === AudienceCapability::Discover
            ? [AudienceCapability::Discover->value, AudienceCapability::Create->value]
            : [AudienceCapability::Create->value];

        return $this->versionCache[$cacheKey] = RequestTypeAudience::query()
            ->whereIn('capability', $capabilities)
            ->where(function ($query) use ($userId, $roleIds): void {
                $query->where(fn ($user) => $user->where('actor_type', 'user')->where('actor_id', $userId));
                if ($roleIds !== []) {
                    $query->orWhere(fn ($role) => $role->where('actor_type', 'role')->whereIn('actor_id', $roleIds));
                }
            })
            ->distinct()
            ->orderBy('request_type_version_id')
            ->pluck('request_type_version_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
