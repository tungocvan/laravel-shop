<?php

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerService
{
    public function create(array $data): User
    {
        return DB::transaction(fn (): User => User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]));
    }

    public function update(int $userId, array $data): User
    {
        return DB::transaction(function () use ($userId, $data): User {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ];

            if (! empty($data['password'])) {
                $attributes['password'] = Hash::make($data['password']);
            }

            $user->update($attributes);

            return $user->refresh();
        });
    }

    public function toggleStatus(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $user->update(['is_active' => ! $user->is_active]);
        });
    }

    public function delete(int $userId): void
    {
        User::query()->findOrFail($userId)->delete();
    }

    public function deleteMany(array $userIds): int
    {
        $ids = collect($userIds)->map(fn ($id): int => (int) $id)->filter()->unique();

        return DB::transaction(function () use ($ids): int {
            $users = User::query()->whereIn('id', $ids)->get();
            $users->each->delete();

            return $users->count();
        });
    }

    public function query(array $filters = []): Builder
    {
        return User::query()
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest();
    }
}
