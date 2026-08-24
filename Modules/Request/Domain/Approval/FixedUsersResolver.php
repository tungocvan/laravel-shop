<?php

namespace Modules\Request\Domain\Approval;

use Illuminate\Validation\ValidationException;
use Modules\Request\Contracts\ActorResolver;
use Modules\Request\Domain\Enums\CandidateSource;
use Modules\User\Contracts\UserDirectory;

final class FixedUsersResolver implements ActorResolver
{
    public function __construct(private readonly UserDirectory $users) {}

    public function key(): string
    {
        return 'fixed_users';
    }

    public function resolve(ActorResolutionContext $context): ResolvedActors
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) data_get($context->stage->resolver_config_json, 'user_ids', [])), fn (int $id): bool => $id > 0)));
        $limit = (int) config('request.settings.max_candidates_per_stage', 100);
        if ($ids === [] || count($ids) > $limit) {
            throw ValidationException::withMessages(['stage' => ['resolver_config_invalid']]);
        }

        return new ResolvedActors($this->users->findManyActive($ids, $limit), CandidateSource::FixedUser, null);
    }
}
