<?php

namespace Modules\User\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Modules\User\Contracts\UserDirectory;
use Modules\User\Data\UserIdentity;

final class AuthUserDirectory implements UserDirectory
{
    public function findActive(int $userId): ?UserIdentity
    {
        if ($userId < 1) {
            return null;
        }

        $model = $this->newAuthModel();
        $record = $this->activeQuery($model)->find($userId);

        return $record instanceof Model ? $this->toIdentity($record) : null;
    }

    public function findManyActive(array $userIds, int $limit): array
    {
        $limit = $this->validatedLimit($limit);
        $ids = collect($userIds)
            ->filter(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->take($limit)
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $model = $this->newAuthModel();
        $records = $this->activeQuery($model)
            ->whereKey($ids->all())
            ->get()
            ->keyBy(fn (Model $record): int => (int) $record->getKey());

        return $ids
            ->map(fn (int $id): ?UserIdentity => ($record = $records->get($id)) instanceof Model
                ? $this->toIdentity($record)
                : null)
            ->filter()
            ->values()
            ->all();
    }

    public function searchActive(string $term, int $limit): array
    {
        $limit = $this->validatedLimit($limit);
        $term = trim($term);
        $model = $this->newAuthModel();
        $columns = $this->availableColumns($model);
        $searchable = array_values(array_intersect(['name', 'email'], $columns));

        if ($term === '' || $searchable === []) {
            return [];
        }

        return $this->activeQuery($model)
            ->where(function (Builder $query) use ($searchable, $term): void {
                foreach ($searchable as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}($column, 'like', '%'.$term.'%');
                }
            })
            ->orderBy(in_array('name', $columns, true) ? 'name' : $model->getKeyName())
            ->orderBy($model->getKeyName())
            ->limit($limit)
            ->get()
            ->map(fn (Model $record): UserIdentity => $this->toIdentity($record))
            ->all();
    }

    private function newAuthModel(): Model
    {
        $provider = (string) config('auth.guards.admin.provider', '');
        $driver = (string) config("auth.providers.{$provider}.driver", '');
        $modelClass = config("auth.providers.{$provider}.model");

        if ($provider === '' || $driver !== 'eloquent' || ! is_string($modelClass) || ! class_exists($modelClass)) {
            throw new LogicException('The admin auth provider must use a valid Eloquent model.');
        }

        $model = new $modelClass;

        if (! $model instanceof Model || ! $model instanceof Authenticatable) {
            throw new LogicException('The admin auth provider model must be an Eloquent authentication identity.');
        }

        return $model;
    }

    private function activeQuery(Model $model): Builder
    {
        $query = $model->newQuery()->select($this->safeColumns($model));

        if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'is_active')) {
            $query->where('is_active', true);
        }

        return $query;
    }

    /** @return list<string> */
    private function safeColumns(Model $model): array
    {
        $available = $this->availableColumns($model);
        $safe = array_values(array_intersect([
            $model->getKeyName(),
            'name',
            'email',
            'avatar',
            'is_active',
            'locale',
            'timezone',
        ], $available));

        if (! in_array($model->getKeyName(), $safe, true)) {
            throw new LogicException('The admin auth identity key is unavailable.');
        }

        return $safe;
    }

    /** @return list<string> */
    private function availableColumns(Model $model): array
    {
        return Schema::connection($model->getConnectionName())->getColumnListing($model->getTable());
    }

    private function toIdentity(Model $record): UserIdentity
    {
        $name = trim((string) $record->getAttribute('name'));

        return new UserIdentity(
            id: (int) $record->getKey(),
            displayName: $name !== '' ? $name : 'User #'.$record->getKey(),
            maskedEmail: $this->maskEmail($record->getAttribute('email')),
            avatarReference: $this->nullableString($record->getAttribute('avatar')),
            active: $record->getAttribute('is_active') === null || (bool) $record->getAttribute('is_active'),
            locale: $this->nullableString($record->getAttribute('locale')),
            timezone: $this->nullableString($record->getAttribute('timezone')),
        );
    }

    private function maskEmail(mixed $email): ?string
    {
        if (! is_string($email) || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 1);

        return $visible.str_repeat('*', max(2, mb_strlen($local) - 1)).'@'.$domain;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function validatedLimit(int $limit): int
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('User directory limit must be between 1 and 100.');
        }

        return $limit;
    }
}
