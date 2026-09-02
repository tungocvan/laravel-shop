<?php

namespace Modules\Auth\Services;

use App\Models\User;

/**
 * @deprecated Use GoogleIdentityService directly. This compatibility adapter
 * remains temporarily because repository-wide caller proof is incomplete.
 */
class GoogleWebAuthService
{
    public function __construct(
        private readonly GoogleIdentityService $identities,
    ) {}

    public function resolve(object $googleUser): User
    {
        return $this->identities->resolve($googleUser);
    }

    public function resolveExisting(object $googleUser): User
    {
        return $this->identities->resolveExisting($googleUser);
    }

    public function link(User $user, object $googleUser): User
    {
        return $this->identities->link($user, $googleUser);
    }
}
