<?php

namespace Modules\Role\Contracts;

use Modules\Role\Data\RoleIdentity;

interface RoleDirectory
{
    public function findAdminRole(int $roleId): ?RoleIdentity;

    /** @return list<int> */
    public function activeMemberIds(int $roleId, int $limit): array;

    /** @return list<int> */
    public function activeAdminRoleIdsForUser(int $userId, int $limit): array;

    /** @return list<RoleIdentity> */
    public function searchAdminRoles(string $term, int $limit): array;
}
