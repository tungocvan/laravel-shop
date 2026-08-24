<?php

namespace Modules\Role\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Role\Contracts\RoleDirectory;
use Modules\Role\Data\RoleIdentity;
use Modules\Role\Exceptions\RoleDirectoryException;
use Modules\User\Contracts\UserDirectory;
use Spatie\Permission\Contracts\Role as RoleContract;

final class SpatieRoleDirectory implements RoleDirectory
{
    private const ADMIN_GUARD = 'admin';

    public function __construct(private readonly UserDirectory $users) {}

    public function findAdminRole(int $roleId): ?RoleIdentity
    {
        if ($roleId < 1) {
            return null;
        }

        $role = $this->newRoleModel()
            ->newQuery()
            ->select(['id', 'name', 'guard_name'])
            ->where('guard_name', self::ADMIN_GUARD)
            ->find($roleId);

        return $role instanceof Model ? $this->toIdentity($role) : null;
    }

    public function activeMemberIds(int $roleId, int $limit): array
    {
        $limit = $this->validatedLimit($limit);
        $roleModel = $this->newRoleModel();
        $role = $roleModel->newQuery()->select(['id', 'guard_name'])->find($roleId);

        if (! $role instanceof Model) {
            throw new RoleDirectoryException('not_found');
        }

        if ($role->getAttribute('guard_name') !== self::ADMIN_GUARD) {
            throw new RoleDirectoryException('wrong_guard');
        }

        $authModel = $this->authModel();
        $table = (string) config('permission.table_names.model_has_roles', 'model_has_roles');
        $rolePivotKey = (string) (config('permission.column_names.role_pivot_key') ?: 'role_id');
        $modelKey = (string) config('permission.column_names.model_morph_key', 'model_id');

        $memberIds = DB::table($table)
            ->where($rolePivotKey, $roleId)
            ->where('model_type', $authModel->getMorphClass())
            ->orderBy($modelKey)
            ->limit($limit + 1)
            ->pluck($modelKey)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($memberIds->count() > $limit) {
            throw new RoleDirectoryException('candidate_limit_exceeded');
        }

        return collect($this->users->findManyActive($memberIds->all(), $limit))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function activeAdminRoleIdsForUser(int $userId, int $limit): array
    {
        $limit = $this->validatedLimit($limit);
        if ($this->users->findActive($userId) === null) {
            return [];
        }

        $roleModel = $this->newRoleModel();
        $table = (string) config('permission.table_names.model_has_roles', 'model_has_roles');
        $rolePivotKey = (string) (config('permission.column_names.role_pivot_key') ?: 'role_id');
        $modelKey = (string) config('permission.column_names.model_morph_key', 'model_id');
        $roleIds = DB::table($table)
            ->join($roleModel->getTable(), $roleModel->qualifyColumn('id'), '=', $table.'.'.$rolePivotKey)
            ->where($table.'.'.$modelKey, $userId)
            ->where($table.'.model_type', $this->authModel()->getMorphClass())
            ->where($roleModel->qualifyColumn('guard_name'), self::ADMIN_GUARD)
            ->orderBy($table.'.'.$rolePivotKey)
            ->limit($limit + 1)
            ->pluck($table.'.'.$rolePivotKey)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($roleIds->count() > $limit) {
            throw new RoleDirectoryException('candidate_limit_exceeded');
        }

        return $roleIds->all();
    }

    public function searchAdminRoles(string $term, int $limit): array
    {
        $limit = $this->validatedLimit($limit);
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        return $this->newRoleModel()
            ->newQuery()
            ->select(['id', 'name', 'guard_name'])
            ->where('guard_name', self::ADMIN_GUARD)
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (Model $role): RoleIdentity => $this->toIdentity($role))
            ->all();
    }

    private function newRoleModel(): Model
    {
        $modelClass = config('permission.models.role');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            throw new LogicException('The configured Spatie role model is invalid.');
        }

        $model = new $modelClass;

        if (! $model instanceof Model || ! $model instanceof RoleContract) {
            throw new LogicException('The configured role model must be an Eloquent Spatie role.');
        }

        return $model;
    }

    private function authModel(): Model
    {
        $provider = (string) config('auth.guards.admin.provider', '');
        $modelClass = config("auth.providers.{$provider}.model");

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            throw new LogicException('The configured admin auth model is invalid.');
        }

        $model = new $modelClass;

        if (! $model instanceof Model) {
            throw new LogicException('The configured admin auth model must be an Eloquent model.');
        }

        return $model;
    }

    private function toIdentity(Model $role): RoleIdentity
    {
        return new RoleIdentity(
            id: (int) $role->getKey(),
            name: (string) $role->getAttribute('name'),
            guard: (string) $role->getAttribute('guard_name'),
        );
    }

    private function validatedLimit(int $limit): int
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Role directory limit must be between 1 and 100.');
        }

        return $limit;
    }
}
