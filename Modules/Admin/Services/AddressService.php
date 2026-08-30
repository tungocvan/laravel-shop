<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Modules\User\Services\UserAddressService;

/**
 * @deprecated Canonical user-address behavior is owned by Modules\User.
 * This compatibility facade preserves the historical Admin API while delegating all behavior.
 */
class AddressService
{
    public function __construct(private readonly UserAddressService $addresses)
    {
    }

    public function getUserAddresses($userId)
    {
        return $this->addresses->forUser((int) $userId);
    }

    public function create($userId, array $data)
    {
        return $this->addresses->create((int) $userId, $data);
    }

    public function update($addressId, $userId, array $data)
    {
        return $this->addresses->update((int) $addressId, (int) $userId, $data);
    }

    public function delete($addressId, $userId)
    {
        $this->addresses->delete((int) $addressId, (int) $userId);

        return true;
    }

    public function setDefault($addressId, $userId)
    {
        return $this->addresses->setDefault((int) $addressId, (int) $userId);
    }
}
