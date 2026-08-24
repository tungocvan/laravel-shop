<?php

namespace Modules\Request\Domain\Approval;

use Illuminate\Validation\ValidationException;
use Modules\Request\Contracts\ActorResolver;

final class ActorResolverRegistry
{
    /** @var array<string, ActorResolver> */
    private array $resolvers;

    public function __construct(FixedUsersResolver $fixedUsers, RoleMembersResolver $roleMembers, FormUserFieldResolver $formUserField)
    {
        $this->resolvers = collect([$fixedUsers, $roleMembers, $formUserField])->mapWithKeys(fn (ActorResolver $resolver): array => [$resolver->key() => $resolver])->all();
    }

    public function resolve(string $key): ActorResolver
    {
        return $this->resolvers[$key] ?? throw ValidationException::withMessages(['stage' => ['resolver_not_registered']]);
    }
}
