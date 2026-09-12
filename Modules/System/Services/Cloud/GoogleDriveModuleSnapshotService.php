<?php

declare(strict_types=1);

namespace Modules\System\Services\Cloud;

use Illuminate\Support\Facades\Http;
use Modules\System\Services\Database\ModuleSnapshotService;
use RuntimeException;

class GoogleDriveModuleSnapshotService
{
    private const MAX_LIST_LIMIT = 100;

    private const MAX_DOWNLOAD_BYTES = 1024 * 1024 * 1024;

    public function __construct(
        private readonly GoogleDriveConnectionService $drive,
        private readonly ModuleSnapshotService $snapshots,
    ) {}

    public function list(string $module, int $limit = 50): array
    {
        $this->snapshots->tablesForModule($module);
        $token = $this->drive->accessToken();
        $rootId = $this->rootFolderId();
        $databaseId = $this->findChildFolder($token, $rootId, 'database');

        if ($databaseId === null) {
            return [];
        }

        $modulesId = $this->findChildFolder($token, $databaseId, 'modules');
        if ($modulesId === null) {
            return [];
        }

        $moduleId = $this->findChildFolder($token, $modulesId, $module);
        if ($moduleId === null) {
            return [];
        }

        $files = [];
        foreach ($this->listFolders($token, $moduleId, 10) as $year) {
            if (! preg_match('/\A\d{4}\z/', (string) ($year['name'] ?? ''))) {
                continue;
            }

            foreach ($this->listFolders($token, (string) $year['id'], 12) as $month) {
                if (! preg_match('/\A(?:0[1-9]|1[0-2])\z/', (string) ($month['name'] ?? ''))) {
                    continue;
                }

                foreach ($this->listFiles($token, (string) $month['id'], 100) as $file) {
                    $name = (string) ($file['name'] ?? '');
                    if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\.zip\z/i', $name)) {
                        continue;
                    }

                    $record = [
                        'id' => (string) $file['id'],
                        'name' => $name,
                        'size' => (int) ($file['size'] ?? 0),
                        'created_at' => $file['createdTime'] ?? null,
                        'modified_at' => $file['modifiedTime'] ?? null,
                        'module' => $module,
                        'year' => (string) $year['name'],
                        'month' => (string) $month['name'],
                    ];
                    $record['reference'] = $this->reference($record);
                    $record['url'] = 'https://drive.google.com/file/d/'.$record['id'].'/view';
                    $files[] = $record;

                    if (count($files) >= self::MAX_LIST_LIMIT) {
                        break 3;
                    }
                }
            }
        }

        usort($files, static fn (array $a, array $b): int => strcmp(
            (string) ($b['modified_at'] ?? ''),
            (string) ($a['modified_at'] ?? ''),
        ));

        return array_slice($files, 0, max(1, min($limit, self::MAX_LIST_LIMIT)));
    }

    public function uploadLocal(string $module, string $localReference): array
    {
        $snapshot = $this->snapshots->resolveLocalReference($localReference, $module);

        if ($snapshot === null) {
            throw new RuntimeException('Module snapshot local không tồn tại.');
        }

        $this->snapshots->validatePackage($snapshot['absolute_path'], $module, enforceSchema: false);
        $token = $this->drive->accessToken();
        $rootId = $this->rootFolderId();
        $databaseId = $this->ensureChildFolder($token, $rootId, 'database');
        $modulesId = $this->ensureChildFolder($token, $databaseId, 'modules');
        $moduleId = $this->ensureChildFolder($token, $modulesId, $module);
        $createdAt = $snapshot['created_at'] ? \Carbon\Carbon::parse((string) $snapshot['created_at']) : now();
        $yearId = $this->ensureChildFolder($token, $moduleId, $createdAt->format('Y'));
        $monthId = $this->ensureChildFolder($token, $yearId, $createdAt->format('m'));
        $create = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(30)
            ->post('https://www.googleapis.com/drive/v3/files', [
                'name' => $snapshot['name'],
                'parents' => [$monthId],
                'mimeType' => 'application/zip',
            ]);

        if (! $create->successful() || trim((string) $create->json('id')) === '') {
            throw new RuntimeException('Không thể tạo module snapshot trên Google Drive. HTTP '.$create->status());
        }

        $fileId = (string) $create->json('id');
        $stream = fopen($snapshot['absolute_path'], 'rb');

        if ($stream === false) {
            $this->deleteFileId($token, $fileId);
            throw new RuntimeException('Không thể mở module snapshot để upload.');
        }

        try {
            $upload = Http::withToken($token)
                ->withBody($stream, 'application/zip')
                ->timeout(600)
                ->patch('https://www.googleapis.com/upload/drive/v3/files/'.rawurlencode($fileId).'?uploadType=media');
        } finally {
            fclose($stream);
        }

        if (! $upload->successful()) {
            $this->deleteFileId($token, $fileId);
            throw new RuntimeException('Upload module snapshot lên Google Drive thất bại. HTTP '.$upload->status());
        }

        return [
            'id' => $fileId,
            'name' => $snapshot['name'],
            'module' => $module,
            'size' => $snapshot['size'],
            'uploaded_at' => now()->toIso8601String(),
        ];
    }

    public function downloadToLocal(string $module, string $remoteReference): array
    {
        $remote = $this->resolveReference($module, $remoteReference);

        if ($remote['size'] <= 0 || $remote['size'] > self::MAX_DOWNLOAD_BYTES) {
            throw new RuntimeException('Module snapshot trên Google Drive có dung lượng không hợp lệ.');
        }

        $temporaryPath = tempnam(storage_path('framework'), 'module-drive-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Không thể tạo file tạm để tải module snapshot.');
        }

        try {
            $response = Http::withToken($this->drive->accessToken())
                ->withOptions(['sink' => $temporaryPath])
                ->connectTimeout(20)
                ->timeout(600)
                ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($remote['id']), ['alt' => 'media']);

            if (! $response->successful()) {
                throw new RuntimeException('Không thể tải module snapshot từ Google Drive. HTTP '.$response->status());
            }

            return $this->snapshots->importDownloadedPackage($temporaryPath, $module, $remote['name']);
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function delete(string $module, string $remoteReference): void
    {
        $remote = $this->resolveReference($module, $remoteReference);
        $this->deleteFileId($this->drive->accessToken(), $remote['id']);
    }

    private function resolveReference(string $module, string $reference): array
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $reference)) {
            throw new RuntimeException('Google Drive module snapshot reference không hợp lệ.');
        }

        foreach ($this->list($module, self::MAX_LIST_LIMIT) as $remote) {
            if (hash_equals($remote['reference'], $reference)) {
                return $remote;
            }
        }

        throw new RuntimeException('Google Drive module snapshot không còn tồn tại trong vùng được phép.');
    }

    private function rootFolderId(): string
    {
        $rootId = trim((string) ($this->drive->status()['folder_id'] ?? ''));

        if ($rootId === '') {
            $this->drive->testConnection();
            $rootId = trim((string) ($this->drive->status()['folder_id'] ?? ''));
        }

        if ($rootId === '') {
            throw new RuntimeException('Không xác định được thư mục gốc Google Drive backup.');
        }

        return $rootId;
    }

    private function reference(array $file): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new RuntimeException('Application key is required for module snapshot references.');
        }

        return hash_hmac('sha256', implode('|', [
            'system-drive-module-snapshot',
            $file['id'],
            $file['name'],
            $file['module'],
            $file['year'],
            $file['month'],
        ]), $key);
    }

    private function ensureChildFolder(string $token, string $parentId, string $name): string
    {
        $existing = $this->findChildFolder($token, $parentId, $name);
        if ($existing !== null) {
            return $existing;
        }

        $response = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(30)
            ->post('https://www.googleapis.com/drive/v3/files', [
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentId],
            ]);

        if (! $response->successful() || trim((string) $response->json('id')) === '') {
            throw new RuntimeException('Không thể tạo thư mục module snapshot trên Google Drive. HTTP '.$response->status());
        }

        return (string) $response->json('id');
    }

    private function findChildFolder(string $token, string $parentId, string $name): ?string
    {
        $escaped = str_replace("'", "\\'", $name);
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "name = '{$escaped}' and mimeType = 'application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed = false",
            'fields' => 'files(id,name)',
            'pageSize' => 10,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể đọc thư mục module snapshot Google Drive. HTTP '.$response->status());
        }

        return (string) ($response->json('files.0.id') ?: '') ?: null;
    }

    private function listFolders(string $token, string $parentId, int $limit): array
    {
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "mimeType = 'application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed = false",
            'fields' => 'files(id,name)',
            'pageSize' => max(1, min($limit, 100)),
            'orderBy' => 'name desc',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể đọc cây module snapshot Google Drive. HTTP '.$response->status());
        }

        return is_array($response->json('files')) ? $response->json('files') : [];
    }

    private function listFiles(string $token, string $parentId, int $limit): array
    {
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "mimeType != 'application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed = false",
            'fields' => 'files(id,name,size,createdTime,modifiedTime)',
            'pageSize' => max(1, min($limit, 100)),
            'orderBy' => 'modifiedTime desc',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể đọc module snapshot Google Drive. HTTP '.$response->status());
        }

        return is_array($response->json('files')) ? $response->json('files') : [];
    }

    private function deleteFileId(string $token, string $fileId): void
    {
        $response = Http::withToken($token)
            ->timeout(30)
            ->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId));

        if (! $response->successful() && $response->status() !== 404) {
            throw new RuntimeException('Không thể xóa module snapshot trên Google Drive. HTTP '.$response->status());
        }
    }
}
