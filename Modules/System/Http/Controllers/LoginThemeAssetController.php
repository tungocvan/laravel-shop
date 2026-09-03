<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\System\Services\SettingsService;
use Throwable;

class LoginThemeAssetController extends Controller
{
    public function store(Request $request, string $type, SettingsService $settings): RedirectResponse
    {
        abort_unless(in_array($type, ['logo', 'background'], true), 404);

        $validated = $request->validateWithBag(
            $type === 'logo' ? 'logoUpload' : 'backgroundUpload',
            [
                'target' => ['required', Rule::in(['admin', 'client'])],
                'asset' => [
                    'required',
                    'image',
                    'mimes:png,jpg,jpeg,webp',
                    'max:'.($type === 'logo' ? 3072 : 6144),
                ],
            ],
            [
                'asset.required' => 'Vui lòng chọn hình ảnh cần tải lên.',
                'asset.image' => 'Tệp được chọn phải là hình ảnh hợp lệ.',
                'asset.mimes' => 'Chỉ chấp nhận ảnh PNG, JPG, JPEG hoặc WebP.',
                'asset.max' => $type === 'logo'
                    ? 'Logo không được vượt quá 3 MB.'
                    : 'Ảnh nền không được vượt quá 6 MB.',
            ],
        );

        $target = $validated['target'];
        $prefix = $target === 'admin' ? 'auth_login_admin_' : 'auth_login_client_';
        $key = $prefix.$type;
        $directory = $type === 'logo' ? 'login-branding/logos' : 'login-branding/backgrounds';
        $oldPath = (string) $settings->get($key, '');
        $newPath = $request->file('asset')->store($directory, 'public');

        try {
            $settings->set($key, $newPath, 'auth_login');
        } catch (Throwable $e) {
            Storage::disk('public')->delete($newPath);
            report($e);

            return back()->with('error', 'Không thể tải hình ảnh đăng nhập. Vui lòng kiểm tra log hệ thống.');
        }

        if ($this->isManagedPath($oldPath) && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('admin.system.settings.login-theme', ['target' => $target])
            ->with('success', $type === 'logo' ? 'Đã cập nhật logo đăng nhập.' : 'Đã cập nhật ảnh nền đăng nhập.');
    }

    public function destroy(Request $request, string $type, SettingsService $settings): RedirectResponse
    {
        abort_unless(in_array($type, ['logo', 'background'], true), 404);

        $validated = $request->validate([
            'target' => ['required', Rule::in(['admin', 'client'])],
        ]);

        $target = $validated['target'];
        $prefix = $target === 'admin' ? 'auth_login_admin_' : 'auth_login_client_';
        $key = $prefix.$type;
        $oldPath = (string) $settings->get($key, '');

        $settings->set($key, null, 'auth_login');

        if ($this->isManagedPath($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('admin.system.settings.login-theme', ['target' => $target])
            ->with('success', 'Đã xóa hình ảnh đăng nhập.');
    }

    private function isManagedPath(string $path): bool
    {
        return $path !== '' && str_starts_with($path, 'login-branding/');
    }
}
