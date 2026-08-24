<?php

namespace Modules\Request\Domain\Approval;

final class ActorResolverConfigRegistry
{
    private const KEYS = ['fixed_users', 'role_members', 'form_user_field'];

    public function supports(string $key): bool
    {
        return in_array($key, self::KEYS, true);
    }

    public function keys(): array
    {
        return self::KEYS;
    }
}
