<?php

namespace Modules\User\Contracts;

use Modules\User\Data\UserIdentity;

interface UserDirectory
{
    public function findActive(int $userId): ?UserIdentity;

    /** @return list<UserIdentity> */
    public function findManyActive(array $userIds, int $limit): array;

    /** @return list<UserIdentity> */
    public function searchActive(string $term, int $limit): array;
}
