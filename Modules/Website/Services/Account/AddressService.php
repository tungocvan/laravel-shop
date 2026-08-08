<?php

namespace Modules\Website\Services\Account;

use Modules\Website\Models\UserAddress;
use Illuminate\Support\Facades\Auth;

class AddressService
{
    public function getUserAddresses($userId)
    {
        return UserAddress::where('user_id', $userId)->orderBy('is_default', 'desc')->get();
    }

    public function create($userId, array $data)
    {
        // Nếu user chọn là mặc định, hoặc chưa có địa chỉ nào -> set các cái khác thành false
        if (!empty($data['is_default']) || UserAddress::where('user_id', $userId)->doesntExist()) {
            UserAddress::where('user_id', $userId)->update(['is_default' => false]);
            $data['is_default'] = true;
        }

        return UserAddress::create(array_merge($data, ['user_id' => $userId]));
    }

    public function update($addressId, $userId, array $data)
    {
        $address = UserAddress::where('user_id', $userId)->findOrFail($addressId);

        if (!empty($data['is_default'])) {
            UserAddress::where('user_id', $userId)->update(['is_default' => false]);
        }

        $address->update($data);
        return $address;
    }

    public function delete($addressId, $userId)
    {
        $address = UserAddress::where('user_id', $userId)->findOrFail($addressId);

        // Nếu xóa địa chỉ mặc định, hãy set cái mới nhất còn lại làm mặc định (Optional logic)
        if ($address->is_default) {
            $next = UserAddress::where('user_id', $userId)->where('id', '!=', $addressId)->latest()->first();
            if ($next) $next->update(['is_default' => true]);
        }

        return $address->delete();
    }

    public function setDefault($addressId, $userId)
    {
        UserAddress::where('user_id', $userId)->update(['is_default' => false]);
        UserAddress::where('user_id', $userId)->where('id', $addressId)->update(['is_default' => true]);
    }
}
