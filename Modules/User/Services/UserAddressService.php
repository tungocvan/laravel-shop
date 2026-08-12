<?php

namespace Modules\User\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\UserAddress;

class UserAddressService
{
    public function forUser(int $userId): Collection
    {
        return UserAddress::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->latest()
            ->get();
    }

    public function findForUser(int $addressId, int $userId): UserAddress
    {
        return UserAddress::query()
            ->where('user_id', $userId)
            ->findOrFail($addressId);
    }

    public function create(int $userId, array $data): UserAddress
    {
        return DB::transaction(function () use ($userId, $data): UserAddress {
            $hasNoAddress = UserAddress::query()->where('user_id', $userId)->doesntExist();
            $makeDefault = (bool) ($data['is_default'] ?? false) || $hasNoAddress;

            if ($makeDefault) {
                $this->clearDefault($userId);
            }

            return UserAddress::query()->create([
                ...$data,
                'user_id' => $userId,
                'is_default' => $makeDefault,
            ]);
        });
    }

    public function update(int $addressId, int $userId, array $data): UserAddress
    {
        return DB::transaction(function () use ($addressId, $userId, $data): UserAddress {
            $address = $this->lockedForUser($addressId, $userId);

            if (! empty($data['is_default'])) {
                $this->clearDefault($userId);
            }

            $address->update($data);

            return $address->refresh();
        });
    }

    public function delete(int $addressId, int $userId): void
    {
        DB::transaction(function () use ($addressId, $userId): void {
            $address = $this->lockedForUser($addressId, $userId);
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                UserAddress::query()
                    ->where('user_id', $userId)
                    ->latest()
                    ->first()?->update(['is_default' => true]);
            }
        });
    }

    public function setDefault(int $addressId, int $userId): UserAddress
    {
        return DB::transaction(function () use ($addressId, $userId): UserAddress {
            $address = $this->lockedForUser($addressId, $userId);
            $this->clearDefault($userId);
            $address->update(['is_default' => true]);

            return $address->refresh();
        });
    }

    private function lockedForUser(int $addressId, int $userId): UserAddress
    {
        return UserAddress::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->findOrFail($addressId);
    }

    private function clearDefault(int $userId): void
    {
        UserAddress::query()
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }
}
