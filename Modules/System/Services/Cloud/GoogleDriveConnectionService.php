<?php

namespace Modules\System\Services\Cloud;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\System\Services\SettingsService;
use RuntimeException;

class GoogleDriveConnectionService
{
    private const GROUP = 'cloud_storage';
    private const KEY_ACCESS_TOKEN = 'cloud.google_drive.access_token';
    private const KEY_REFRESH_TOKEN = 'cloud.google_drive.refresh_token';
    private const KEY_EXPIRES_AT = 'cloud.google_drive.expires_at';
    private const KEY_EMAIL = 'cloud.google_drive.email';
    private const KEY_FOLDER_ID = 'cloud.google_drive.folder_id';
    private const KEY_CONNECTED_AT = 'cloud.google_drive.connected_at';
    private const KEY_LAST_CHECKED_AT = 'cloud.google_drive.last_checked_at';

    public function __construct(private readonly SettingsService $settings) {}

    public function authorizationUrl(string $state): string
    {
        $clientId = trim((string) config('system.google_drive.client_id'));
        $redirectUri = trim((string) config('system.google_drive.redirect_uri'));

        if ($clientId === '' || $redirectUri === '') {
            throw new RuntimeException('Google Drive OAuth chưa được cấu hình đầy đủ.');
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', (array) config('system.google_drive.scopes', [])),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function connectFromAuthorizationCode(string $code): array
    {
        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('system.google_drive.client_id'),
            'client_secret' => config('system.google_drive.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('system.google_drive.redirect_uri'),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể đổi Google authorization code thành access token.');
        }

        $payload = $response->json();
        $accessToken = trim((string) ($payload['access_token'] ?? ''));
        $refreshToken = trim((string) ($payload['refresh_token'] ?? ''));
        $expiresIn = max(60, (int) ($payload['expires_in'] ?? 3600));

        if ($accessToken === '') {
            throw new RuntimeException('Google không trả về access token.');
        }

        $existingRefreshToken = $this->secret(self::KEY_REFRESH_TOKEN);
        if ($refreshToken === '') {
            $refreshToken = $existingRefreshToken;
        }

        if ($refreshToken === '') {
            throw new RuntimeException('Google không trả về refresh token. Hãy kết nối lại và cấp quyền offline.');
        }

        $profile = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $profile->successful()) {
            throw new RuntimeException('Không thể đọc thông tin tài khoản Google đã kết nối.');
        }

        $email = trim((string) ($profile->json('email') ?? ''));

        $this->storeSecret(self::KEY_ACCESS_TOKEN, $accessToken);
        $this->storeSecret(self::KEY_REFRESH_TOKEN, $refreshToken);
        $this->settings->set(self::KEY_EXPIRES_AT, now()->addSeconds($expiresIn)->toIso8601String(), self::GROUP);
        $this->settings->set(self::KEY_EMAIL, $email, self::GROUP);
        $this->settings->set(self::KEY_CONNECTED_AT, now()->toIso8601String(), self::GROUP);
        $this->settings->set(self::KEY_LAST_CHECKED_AT, now()->toIso8601String(), self::GROUP);

        $folderId = $this->ensureBackupFolder($accessToken);
        $this->settings->set(self::KEY_FOLDER_ID, $folderId, self::GROUP);

        Log::notice('Google Drive account connected.', [
            'actor_id' => (auth('admin')->user() ?: auth()->user())?->getAuthIdentifier(),
            'email' => $email,
            'folder_id' => $folderId,
        ]);

        return $this->status();
    }

    public function status(): array
    {
        return [
            'connected' => $this->secret(self::KEY_REFRESH_TOKEN) !== '',
            'email' => (string) $this->settings->get(self::KEY_EMAIL, ''),
            'folder_id' => (string) $this->settings->get(self::KEY_FOLDER_ID, ''),
            'folder_name' => (string) config('system.google_drive.folder_name', 'Laravel-Backup'),
            'connected_at' => (string) $this->settings->get(self::KEY_CONNECTED_AT, ''),
            'last_checked_at' => (string) $this->settings->get(self::KEY_LAST_CHECKED_AT, ''),
        ];
    }

    public function testConnection(): array
    {
        $token = $this->accessToken();
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'pageSize' => 1,
                'fields' => 'files(id,name)',
                'spaces' => 'drive',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Google Drive API không phản hồi thành công.');
        }

        $this->settings->set(self::KEY_LAST_CHECKED_AT, now()->toIso8601String(), self::GROUP);

        return $this->status();
    }

    public function disconnect(): void
    {
        $token = $this->secret(self::KEY_ACCESS_TOKEN);
        if ($token !== '') {
            Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', ['token' => $token]);
        }

        foreach ([
            self::KEY_ACCESS_TOKEN,
            self::KEY_REFRESH_TOKEN,
            self::KEY_EXPIRES_AT,
            self::KEY_EMAIL,
            self::KEY_FOLDER_ID,
            self::KEY_CONNECTED_AT,
            self::KEY_LAST_CHECKED_AT,
        ] as $key) {
            $this->settings->set($key, '', self::GROUP);
        }

        Log::notice('Google Drive account disconnected.', [
            'actor_id' => (auth('admin')->user() ?: auth()->user())?->getAuthIdentifier(),
        ]);
    }

    public function accessToken(): string
    {
        $accessToken = $this->secret(self::KEY_ACCESS_TOKEN);
        $expiresAt = (string) $this->settings->get(self::KEY_EXPIRES_AT, '');

        if ($accessToken !== '' && $expiresAt !== '' && now()->addMinute()->lt($expiresAt)) {
            return $accessToken;
        }

        $refreshToken = $this->secret(self::KEY_REFRESH_TOKEN);
        if ($refreshToken === '') {
            throw new RuntimeException('Google Drive chưa được kết nối.');
        }

        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('system.google_drive.client_id'),
            'client_secret' => config('system.google_drive.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể làm mới Google Drive access token.');
        }

        $accessToken = trim((string) $response->json('access_token'));
        if ($accessToken === '') {
            throw new RuntimeException('Google không trả về access token mới.');
        }

        $expiresIn = max(60, (int) ($response->json('expires_in') ?? 3600));
        $this->storeSecret(self::KEY_ACCESS_TOKEN, $accessToken);
        $this->settings->set(self::KEY_EXPIRES_AT, now()->addSeconds($expiresIn)->toIso8601String(), self::GROUP);

        return $accessToken;
    }

    private function ensureBackupFolder(string $accessToken): string
    {
        $name = (string) config('system.google_drive.folder_name', 'Laravel-Backup');
        $query = sprintf(
            "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            str_replace("'", "\\'", $name)
        );

        $list = Http::withToken($accessToken)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id,name)',
            'pageSize' => 10,
        ]);

        if ($list->successful() && is_array($list->json('files')) && count($list->json('files')) > 0) {
            return (string) $list->json('files.0.id');
        }

        $create = Http::withToken($accessToken)->acceptJson()->timeout(20)->post('https://www.googleapis.com/drive/v3/files', [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        if (! $create->successful() || trim((string) $create->json('id')) === '') {
            throw new RuntimeException('Không thể tạo thư mục backup trên Google Drive.');
        }

        return (string) $create->json('id');
    }

    private function storeSecret(string $key, string $value): void
    {
        $this->settings->set($key, $value === '' ? '' : Crypt::encryptString($value), self::GROUP);
    }

    private function secret(string $key): string
    {
        $encrypted = trim((string) $this->settings->get($key, ''));
        if ($encrypted === '') {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return '';
        }
    }
}
