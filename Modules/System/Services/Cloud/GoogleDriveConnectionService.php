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

    private const KEY_FOLDER_NAME = 'cloud.google_drive.folder_name';

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

        $payload = is_array($response->json()) ? $response->json() : [];
        $accessToken = trim((string) ($payload['access_token'] ?? ''));
        $refreshToken = trim((string) ($payload['refresh_token'] ?? '')) ?: $this->secret(self::KEY_REFRESH_TOKEN);

        if ($accessToken === '' || $refreshToken === '') {
            throw new RuntimeException('Google không trả về token OAuth cần thiết.');
        }

        $profile = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $profile->successful()) {
            throw new RuntimeException('Không thể đọc thông tin tài khoản Google đã kết nối.');
        }

        $this->storeSecret(self::KEY_ACCESS_TOKEN, $accessToken);
        $this->storeSecret(self::KEY_REFRESH_TOKEN, $refreshToken);
        $this->settings->set(
            self::KEY_EXPIRES_AT,
            now()->addSeconds(max(60, (int) ($payload['expires_in'] ?? 3600)))->toIso8601String(),
            self::GROUP,
        );
        $this->settings->set(self::KEY_EMAIL, trim((string) ($profile->json('email') ?? '')), self::GROUP);
        $this->settings->set(self::KEY_CONNECTED_AT, now()->toIso8601String(), self::GROUP);
        $this->settings->set(self::KEY_LAST_CHECKED_AT, now()->toIso8601String(), self::GROUP);
        $this->resolveRootFolder($accessToken, true);

        Log::notice('Google Drive account connected.');

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
            $this->throwDriveApiException(
                'Google Drive API không phản hồi thành công.',
                $response->status(),
                $response->json(),
            );
        }

        $this->resolveRootFolder($token);
        $this->settings->set(self::KEY_LAST_CHECKED_AT, now()->toIso8601String(), self::GROUP);

        return $this->status();
    }

    public function markBackupQueued(string $fileName): void
    {
        $this->mergeBackupStatus($fileName, [
            'status' => 'queued',
            'queued_at' => now()->toIso8601String(),
            'started_at' => null,
            'failed_at' => null,
            'error' => null,
        ]);
    }

    public function markBackupProcessing(string $fileName): void
    {
        $this->mergeBackupStatus($fileName, [
            'status' => 'processing',
            'started_at' => now()->toIso8601String(),
            'failed_at' => null,
            'error' => null,
        ]);
    }

    public function markBackupFailed(string $fileName, string $errorCode = 'upload_failed'): void
    {
        $this->mergeBackupStatus($fileName, [
            'status' => 'failed',
            'failed_at' => now()->toIso8601String(),
            'error_code' => $this->safeBackupErrorCode($errorCode),
            'error' => 'Không thể upload backup lên Google Drive. Vui lòng kiểm tra log hệ thống.',
        ]);
    }

    public function uploadBackup(string $path, string $fileName): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File backup local không tồn tại hoặc không đọc được.');
        }

        if ($fileName !== basename($fileName) || ! preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\.sql\z/i', $fileName)) {
            throw new RuntimeException('Tên file backup không hợp lệ.');
        }

        $token = $this->accessToken();
        $rootId = $this->resolveRootFolder($token);
        $databaseId = $this->ensureChildFolder($token, $rootId, 'database');
        $yearId = $this->ensureChildFolder($token, $databaseId, now()->format('Y'));
        $monthId = $this->ensureChildFolder($token, $yearId, now()->format('m'));
        $create = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(30)
            ->post('https://www.googleapis.com/drive/v3/files', [
                'name' => $fileName,
                'parents' => [$monthId],
                'mimeType' => 'application/sql',
            ]);

        if (! $create->successful() || trim((string) $create->json('id')) === '') {
            $this->throwDriveApiException(
                'Không thể tạo file backup trên Google Drive.',
                $create->status(),
                $create->json(),
            );
        }

        $fileId = (string) $create->json('id');
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            $this->deleteCreatedFile($token, $fileId);

            throw new RuntimeException('Không thể mở file backup để upload.');
        }

        try {
            $upload = Http::withToken($token)
                ->withBody($stream, 'application/sql')
                ->timeout(300)
                ->patch('https://www.googleapis.com/upload/drive/v3/files/'.rawurlencode($fileId).'?uploadType=media');
        } finally {
            fclose($stream);
        }

        if (! $upload->successful()) {
            $this->deleteCreatedFile($token, $fileId);
            $this->throwDriveApiException(
                'Upload nội dung backup lên Google Drive thất bại.',
                $upload->status(),
                $upload->json(),
            );
        }

        $uploadedAt = now()->toIso8601String();
        $this->settings->set($this->backupKey($fileName), [
            'status' => 'uploaded',
            'id' => $fileId,
            'uploaded_at' => $uploadedAt,
            'size' => filesize($path) ?: 0,
            'error' => null,
            'failed_at' => null,
        ], self::GROUP, 'json');

        return [
            'id' => $fileId,
            'name' => $fileName,
            'uploaded_at' => $uploadedAt,
        ];
    }

    public function backupStatus(string $fileName): array
    {
        $value = $this->rawBackupStatus($fileName);

        if (($value['status'] ?? '') === 'failed') {
            $value['error_code'] = $this->safeBackupErrorCode((string) ($value['error_code'] ?? ''));
            $value['error'] = 'Không thể upload backup lên Google Drive. Vui lòng kiểm tra log hệ thống.';
        }

        return array_intersect_key($value, array_flip([
            'status',
            'queued_at',
            'started_at',
            'uploaded_at',
            'failed_at',
            'size',
            'error_code',
            'error',
        ]));
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
            self::KEY_FOLDER_NAME,
            self::KEY_CONNECTED_AT,
            self::KEY_LAST_CHECKED_AT,
        ] as $key) {
            $this->settings->set($key, '', self::GROUP);
        }

        Log::notice('Google Drive account disconnected.');
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

        $this->storeSecret(self::KEY_ACCESS_TOKEN, $accessToken);
        $this->settings->set(
            self::KEY_EXPIRES_AT,
            now()->addSeconds(max(60, (int) ($response->json('expires_in') ?? 3600)))->toIso8601String(),
            self::GROUP,
        );

        return $accessToken;
    }

    private function mergeBackupStatus(string $fileName, array $changes): void
    {
        $this->settings->set(
            $this->backupKey($fileName),
            array_merge($this->rawBackupStatus($fileName), $changes),
            self::GROUP,
            'json',
        );
    }

    private function rawBackupStatus(string $fileName): array
    {
        $value = $this->settings->get($this->backupKey($fileName), []);

        return is_array($value) ? $value : [];
    }

    private function resolveRootFolder(string $token, bool $force = false): string
    {
        $name = trim((string) config('system.google_drive.folder_name', 'Laravel-Backup')) ?: 'Laravel-Backup';
        $storedName = trim((string) $this->settings->get(self::KEY_FOLDER_NAME, ''));
        $storedId = trim((string) $this->settings->get(self::KEY_FOLDER_ID, ''));

        if (! $force && $storedId !== '' && $storedName === $name) {
            return $storedId;
        }

        if (! $force && $storedId !== '' && $storedName === '') {
            $folder = $this->getFolderById($token, $storedId);

            if ($folder !== null) {
                $this->settings->set(self::KEY_FOLDER_NAME, $name, self::GROUP);
                Log::notice('Google Drive legacy backup folder metadata upgraded.');

                return $storedId;
            }
        }

        $id = $this->findOrCreateFolder($token, $name, null);
        $this->settings->set(self::KEY_FOLDER_ID, $id, self::GROUP);
        $this->settings->set(self::KEY_FOLDER_NAME, $name, self::GROUP);

        return $id;
    }

    private function getFolderById(string $token, string $folderId): ?array
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($folderId), [
                'fields' => 'id,name,mimeType,trashed',
            ]);

        if (! $response->successful()) {
            Log::warning('Stored Google Drive backup folder could not be verified.', [
                'http_status' => $response->status(),
            ]);

            return null;
        }

        $payload = $response->json();

        if (
            ! is_array($payload)
            || ($payload['mimeType'] ?? '') !== 'application/vnd.google-apps.folder'
            || ($payload['trashed'] ?? false)
        ) {
            return null;
        }

        return $payload;
    }

    private function ensureChildFolder(string $token, string $parentId, string $name): string
    {
        return $this->findOrCreateFolder($token, $name, $parentId);
    }

    private function findOrCreateFolder(string $token, string $name, ?string $parentId): string
    {
        $escaped = str_replace("'", "\\'", $name);
        $query = "name = '{$escaped}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";

        if ($parentId) {
            $query .= " and '{$parentId}' in parents";
        }

        $list = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => $query,
                'spaces' => 'drive',
                'fields' => 'files(id,name)',
                'pageSize' => 10,
            ]);

        if (! $list->successful()) {
            $this->throwDriveApiException(
                'Không thể tìm thư mục backup trên Google Drive.',
                $list->status(),
                $list->json(),
            );
        }

        if (is_array($list->json('files')) && count($list->json('files')) > 0) {
            return (string) $list->json('files.0.id');
        }

        $payload = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];

        if ($parentId) {
            $payload['parents'] = [$parentId];
        }

        $create = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(20)
            ->post('https://www.googleapis.com/drive/v3/files', $payload);

        if (! $create->successful() || trim((string) $create->json('id')) === '') {
            $this->throwDriveApiException(
                'Không thể tạo thư mục backup trên Google Drive.',
                $create->status(),
                $create->json(),
            );
        }

        return (string) $create->json('id');
    }

    private function deleteCreatedFile(string $token, string $fileId): void
    {
        Http::withToken($token)
            ->timeout(20)
            ->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId));
    }

    private function throwDriveApiException(string $message, int $status, mixed $payload): never
    {
        Log::error('Google Drive API request failed.', [
            'http_status' => $status,
            'payload_type' => get_debug_type($payload),
        ]);

        throw new RuntimeException($message." HTTP {$status}.");
    }

    private function safeBackupErrorCode(string $errorCode): string
    {
        return in_array($errorCode, ['upload_failed', 'local_file_missing'], true)
            ? $errorCode
            : 'upload_failed';
    }

    private function backupKey(string $fileName): string
    {
        return 'cloud.google_drive.backup.'.sha1($fileName);
    }

    private function storeSecret(string $key, string $value): void
    {
        $this->settings->set(
            $key,
            $value === '' ? '' : Crypt::encryptString($value),
            self::GROUP,
        );
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
