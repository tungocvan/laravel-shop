<?php

namespace Modules\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Throwable;

class GoogleDriveOAuthController extends Controller
{
    public function connect(Request $request, GoogleDriveConnectionService $service): RedirectResponse
    {
        $this->authorizePermission('system.env.update');

        $state = Str::random(64);
        $request->session()->put('system.google_drive.oauth_state', $state);

        try {
            return redirect()->away($service->authorizationUrl($state));
        } catch (Throwable $e) {
            Log::warning('Google Drive OAuth redirect failed.', ['exception' => $e::class]);

            return redirect()
                ->route('admin.system.settings.env', ['tab' => 'storage'])
                ->with('error', 'Chưa thể bắt đầu kết nối Google Drive. Hãy kiểm tra Client ID, Client Secret và Redirect URI.');
        }
    }

    public function callback(Request $request, GoogleDriveConnectionService $service): RedirectResponse
    {
        $this->authorizePermission('system.env.update');

        $expectedState = (string) $request->session()->pull('system.google_drive.oauth_state', '');
        $receivedState = (string) $request->query('state', '');

        if ($expectedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return redirect()
                ->route('admin.system.settings.env', ['tab' => 'storage'])
                ->with('error', 'Google OAuth state không hợp lệ hoặc đã hết hạn. Vui lòng kết nối lại.');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('admin.system.settings.env', ['tab' => 'storage'])
                ->with('error', 'Google đã từ chối hoặc hủy yêu cầu kết nối.');
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return redirect()
                ->route('admin.system.settings.env', ['tab' => 'storage'])
                ->with('error', 'Google không trả về authorization code.');
        }

        try {
            $status = $service->connectFromAuthorizationCode($code);

            return redirect()
                ->route('admin.system.settings.env', ['tab' => 'storage'])
                ->with('success', 'Đã kết nối Google Drive'.($status['email'] !== '' ? ' với '.$status['email'] : '').'.');
        } catch (Throwable $e) {
            Log::error('Google Drive OAuth callback failed.', [
                'actor_id' => (auth('admin')->user() ?: auth()->user())?->getAuthIdentifier(),
                'exception' => $e::class,
            ]);

            return redirect()
                ->route('admin.system.settings.env', ['tab' => 'storage'])
                ->with('error', 'Không thể hoàn tất kết nối Google Drive. Vui lòng kiểm tra cấu hình và log hệ thống.');
        }
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();
        abort_unless($user?->can($permission), 403);
    }
}
