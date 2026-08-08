<?php

namespace Modules\Website\Services\Account;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProfileService
{
    /**
     * Cập nhật thông tin cơ bản
     */
    public function updateInfo($user, array $data, $avatarFile = null)
    {
        // 1. Xử lý Avatar nếu có upload mới
        if ($avatarFile) {
            // Xóa avatar cũ nếu không phải là avatar mặc định (check logic tùy project)
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Lưu avatar mới: uploads/avatars/user_id_random.jpg
            $filename = $user->id . '_' . time() . '.' . $avatarFile->getClientOriginalExtension();
            $path = $avatarFile->storeAs('uploads/avatars', $filename, 'public');
            $data['avatar'] = $path;
        }

        // 2. Cập nhật Database
        $user->update([
            'name'   => $data['name'],
            'phone'  => $data['phone'],
            'avatar' => $data['avatar'] ?? $user->avatar,
            // Email thường hạn chế cho đổi, hoặc phải có quy trình xác thực riêng.
            // Ở đây tôi cho update luôn theo schema.
            // 'email' => $data['email'],
        ]);

        return $user;
    }

    /**
     * Đổi mật khẩu
     */
    public function updatePassword($user, $currentPassword, $newPassword)
    {
        // Kiểm tra mật khẩu cũ
        if (!Hash::check($currentPassword, $user->password)) {
            throw new Exception('Mật khẩu hiện tại không chính xác.');
        }

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        return true;
    }
}
