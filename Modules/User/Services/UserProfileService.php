<?php

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UserProfileService
{
    public function updateInfo(User $user, array $data, ?UploadedFile $avatarFile = null): User
    {
        $oldAvatar = $user->avatar;

        if ($avatarFile) {
            $filename = $user->id.'_'.time().'.'.$avatarFile->getClientOriginalExtension();
            $data['avatar'] = $avatarFile->storeAs('uploads/avatars', $filename, 'public');
        }

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? $oldAvatar,
        ]);

        if ($avatarFile && $oldAvatar && Storage::disk('public')->exists($oldAvatar)) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $user->refresh();
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new RuntimeException('Mật khẩu hiện tại không chính xác.');
        }

        $user->update(['password' => Hash::make($newPassword)]);
    }
}
