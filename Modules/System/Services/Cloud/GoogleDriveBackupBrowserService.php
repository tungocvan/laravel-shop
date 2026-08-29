<?php

declare(strict_types=1);

namespace Modules\System\Services\Cloud;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDriveBackupBrowserService
{
    private const MAX_LIST_LIMIT = 100;

    private const MAX_RETENTION_SCAN = 1000;

    private const MAX_YEAR_FOLDERS = 10;

    private const MAX_MONTH_FOLDERS = 12;

    private const MAX_DOWNLOAD_BYTES = 500 * 1024 * 1024;

    public function __construct(private readonly GoogleDriveConnectionService $drive) {}

    public function listBackups(int $limit = 100): array
    {
        return array_map(
            fn (array $file): array => $this->safeDescriptor($file),
            $this->scanBackups(max(1, min($limit, self::MAX_LIST_LIMIT))),
        );
    }

    public function applyRetention(int $keep): int
    {
        $keep = max(1, min($keep, self::MAX_RETENTION_SCAN));
        $files = $this->scanBackups(self::MAX_RETENTION_SCAN);
        $deleted = 0;

        foreach (array_slice($files, $keep) as $file) {
            try {
                $this->deleteTrustedFileId($file['id']);
                $deleted++;
            } catch (RuntimeException) {
                // Retention is best-effort; explicit UI delete still reports a safe failure.
            }
        }

        return $deleted;
    }

    public function download(string $reference, string $destination): array
    {
        $file = $this->resolveReference($reference);

        if ($file['size'] <= 0) {
            throw new RuntimeException('Backup Google Drive không có dung lượng hợp lệ.');
        }

        if ($file['size'] > self::MAX_DOWNLOAD_BYTES) {
            throw new RuntimeException('Backup Google Drive vượt quá giới hạn tải qua giao diện quản trị.');
        }

        $response = Http::withToken($this->drive->accessToken())
            ->withOptions(['sink' => $destination])
            ->connectTimeout(20)
            ->timeout(300)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($file['id']), ['alt' => 'media']);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể tải backup từ Google Drive. HTTP '.$response->status());
        }

        return $this->safeDescriptor($file);
    }

    public function delete(string $reference): array
    {
        $file = $this->resolveReference($reference);
        $this->deleteTrustedFileId($file['id']);

        return $this->safeDescriptor($file);
    }

    private function resolveReference(string $reference): array
    {
        if (! preg_match('/\A[a-f0-9]{64}\z/', $reference)) {
            throw new RuntimeException('Google Drive backup reference không hợp lệ.');
        }

        foreach ($this->scanBackups(self::MAX_LIST_LIMIT) as $file) {
            if (hash_equals($this->reference($file), $reference)) {
                return $file;
            }
        }

        throw new RuntimeException('Google Drive backup không tồn tại trong vùng database được phép.');
    }

    private function scanBackups(int $limit): array
    {
        $token = $this->drive->accessToken();
        $rootId = trim((string) ($this->drive->status()['folder_id'] ?? ''));

        if ($rootId === '') {
            $this->drive->testConnection();
            $rootId = trim((string) ($this->drive->status()['folder_id'] ?? ''));
        }

        if ($rootId === '') {
            return [];
        }

        $databaseId = $this->findChildFolder($token, $rootId, 'database');

        if ($databaseId === null) {
            return [];
        }

        $files = [];
        $years = array_slice($this->listFolders($token, $databaseId, self::MAX_YEAR_FOLDERS), 0, self::MAX_YEAR_FOLDERS);

        foreach ($years as $year) {
            if (! preg_match('/\A\d{4}\z/', (string) $year['name'])) {
                continue;
            }

            $months = array_slice($this->listFolders($token, $year['id'], self::MAX_MONTH_FOLDERS), 0, self::MAX_MONTH_FOLDERS);

            foreach ($months as $month) {
                if (! preg_match('/\A(?:0[1-9]|1[0-2])\z/', (string) $month['name'])) {
                    continue;
                }

                $remaining = $limit - count($files);

                if ($remaining <= 0) {
                    break 2;
                }

                foreach ($this->listFiles($token, $month['id'], min(100, $remaining)) as $file) {
                    $name = (string) ($file['name'] ?? '');

                    if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\.sql\z/i', $name)) {
                        continue;
                    }

                    $files[] = [
                        'id' => (string) $file['id'],
                        'name' => $name,
                        'size' => (int) ($file['size'] ?? 0),
                        'created_at' => $file['createdTime'] ?? null,
                        'modified_at' => $file['modifiedTime'] ?? null,
                        'year' => (string) $year['name'],
                        'month' => (string) $month['name'],
                    ];
                }
            }
        }

        usort($files, static fn (array $left, array $right): int => strcmp(
            (string) ($right['modified_at'] ?? ''),
            (string) ($left['modified_at'] ?? ''),
        ));

        return array_slice($files, 0, $limit);
    }

    private function safeDescriptor(array $file): array
    {
        return [
            'reference' => $this->reference($file),
            'name' => $file['name'],
            'size' => $file['size'],
            'created_at' => $file['created_at'],
            'modified_at' => $file['modified_at'],
            'year' => $file['year'],
            'month' => $file['month'],
            'url' => 'https://drive.google.com/file/d/'.$file['id'].'/view',
        ];
    }

    private function reference(array $file): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('Application key is required for Google Drive backup references.');
        }

        return hash_hmac('sha256', implode('|', [
            'system-drive-backup',
            $file['id'],
            $file['name'],
            $file['year'],
            $file['month'],
        ]), $key);
    }

    private function deleteTrustedFileId(string $fileId): void
    {
        $response = Http::withToken($this->drive->accessToken())
            ->timeout(30)
            ->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId));

        if (! $response->successful() && $response->status() !== 404) {
            throw new RuntimeException('Không thể xóa backup trên Google Drive. HTTP '.$response->status());
        }
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
            throw new RuntimeException('Không thể đọc thư mục backup Google Drive. HTTP '.$response->status());
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
            throw new RuntimeException('Không thể đọc cây thư mục Google Drive. HTTP '.$response->status());
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
            throw new RuntimeException('Không thể lấy danh sách backup Google Drive. HTTP '.$response->status());
        }

        return is_array($response->json('files')) ? $response->json('files') : [];
    }
}
