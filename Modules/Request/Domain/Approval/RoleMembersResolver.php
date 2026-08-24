<?php

namespace Modules\Request\Domain\Approval;

use Illuminate\Validation\ValidationException;
use Modules\Request\Contracts\ActorResolver;
use Modules\Request\Domain\Enums\CandidateSource;
use Modules\Role\Contracts\RoleDirectory;
use Modules\User\Contracts\UserDirectory;

final class RoleMembersResolver implements ActorResolver
{
    public function __construct(private readonly RoleDirectory $roles, private readonly UserDirectory $users) {}

    public function key(): string
    {
        return 'role_members';
    }

    public function resolve(ActorResolutionContext $context): ResolvedActors
    {
        $roleId = (int) data_get($context->stage->resolver_config_json, 'role_id', 0);
        if ($roleId < 1 || $this->roles->findAdminRole($roleId) === null) {
            throw ValidationException::withMessages(['stage' => ['role_unavailable']]);
        }
        $limit = (int) config('request.settings.max_candidates_per_stage', 100);
        try {
            $ids = $this->roles->activeMemberIds($roleId, $limit);
        } catch (\RuntimeException) {
            throw ValidationException::withMessages(['stage' => ['actor_resolution_failed']]);
        }

        return new ResolvedActors($this->users->findManyActive($ids, $limit), CandidateSource::RoleMember, (string) $roleId);
    }
}
