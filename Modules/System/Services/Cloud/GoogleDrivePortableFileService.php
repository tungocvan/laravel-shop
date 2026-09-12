<?php

declare(strict_types=1);

namespace Modules\System\Services\Cloud;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDrivePortableFileService
{
    private const MAX_DOWNLOAD_BYTES = 10 * 1024 * 1024;

    public function __construct(private readonly GoogleDriveConnectionService $drive) {}

    public function status(): array
    {
        return $this->drive->status();
    }

    public function put(string $relativePath, string $content, string $mimeType = 'application/octet-stream'): array
    {
        [$folders, $fileName] = $this->splitPath($relativePath);
        $token = $this->drive->accessToken();
        $parentId = $this->rootFolderId();

        foreach ($folders as $folder) {
            $parentId = $this->ensureChildFolder($token, $parentId, $folder);
        }

        $existing = $this->findChildFile($token, $parentId, $fileName);
        $fileId = $existing['id'] ?? null;

        if (! is_string($fileId) || $fileId === '') {
            $create = Http::withToken($token)
                ->asJson()
                ->acceptJson()
                ->timeout(30)
                ->post('https://www.googleapis.com/drive/v3/files', [
                    'name' => $fileName,
                    'parents' => [$parentId],
                    'mimeType' => $mimeType,
                ]);

            if (! $create->successful() || trim((string) $create->json('id')) === '') {
                throw new RuntimeException('Không thể tạo portable file trên Google Drive. HTTP '.$create->status());
            }

            $fileId = (string) $create->json('id');
        }

        $upload = Http::withToken($token)
            ->withBody($content, $mimeType)
            ->timeout(60)
            ->patch('https://www.googleapis.com/upload/drive/v3/files/'.rawurlencode($fileId).'?uploadType=media');

        if (! $upload->successful()) {
            throw new RuntimeException('Không thể upload portable file lên Google Drive. HTTP '.$upload->status());
        }

        return [
            'id' => $fileId,
            'path' => $relativePath,
            'name' => $fileName,
            'size' => strlen($content),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function get(string $relativePath): array
    {
        [$folders, $fileName] = $this->splitPath($relativePath);
        $token = $this->drive->accessToken();
        $parentId = $this->rootFolderId();

        foreach ($folders as $folder) {
            $parentId = $this->findChildFolder($token, $parentId, $folder)
                ?? throw new RuntimeException('Không tìm thấy thư mục portable file trên Google Drive.');
        }

        $file = $this->findChildFile($token, $parentId, $fileName);

        if ($file === null) {
            throw new RuntimeException('Không tìm thấy portable file trên Google Drive.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > self::MAX_DOWNLOAD_BYTES) {
            throw new RuntimeException('Portable file vượt quá giới hạn tải cho phép.');
        }

        $response = Http::withToken($token)
            ->connectTimeout(20)
            ->timeout(60)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode((string) $file['id']), ['alt' => 'media']);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể tải portable file từ Google Drive. HTTP '.$response->status());
        }

        $content = $response->body();
        if (strlen($content) > self::MAX_DOWNLOAD_BYTES) {
            throw new RuntimeException('Portable file vượt quá giới hạn tải cho phép.');
        }

        return [
            'id' => (string) $file['id'],
            'path' => $relativePath,
            'name' => $fileName,
            'size' => $size > 0 ? $size : strlen($content),
            'modified_at' => $file['modifiedTime'] ?? null,
            'content' => $content,
        ];
    }

    public function list(string $relativeDirectory): array
    {
        $folders = $this->splitDirectory($relativeDirectory);
        $token = $this->drive->accessToken();
        $parentId = $this->rootFolderId();

        foreach ($folders as $folder) {
            $parentId = $this->findChildFolder($token, $parentId, $folder);
            if ($parentId === null) {
                return [];
            }
        }

        $files = [];
        $pageToken = null;

        do {
            $parameters = [
                'q' => "mimeType != 'application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed = false",
                'fields' => 'nextPageToken,files(id,name,size,mimeType,modifiedTime)',
                'pageSize' => 100,
                'orderBy' => 'modifiedTime desc',
            ];

            if ($pageToken !== null) {
                $parameters['pageToken'] = $pageToken;
            }

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get('https://www.googleapis.com/drive/v3/files', $parameters);

            if (! $response->successful()) {
                throw new RuntimeException('Không thể liệt kê portable files trên Google Drive. HTTP '.$response->status());
            }

            foreach ((array) $response->json('files', []) as $file) {
                if (! is_array($file)) {
                    continue;
                }

                $files[] = [
                    'id' => (string) ($file['id'] ?? ''),
                    'name' => (string) ($file['name'] ?? ''),
                    'size' => (int) ($file['size'] ?? 0),
                    'mime_type' => (string) ($file['mimeType'] ?? ''),
                    'modified_at' => $file['modifiedTime'] ?? null,
                    'path' => trim($relativeDirectory, '/').'/'.(string) ($file['name'] ?? ''),
                ];
            }

            $pageToken = trim((string) ($response->json('nextPageToken') ?? '')) ?: null;
        } while ($pageToken !== null);

        return $files;
    }

    public function rename(string $relativePath, string $newFileName): array
    {
        [$folders, $fileName] = $this->splitPath($relativePath);
        $this->validatedSegments($newFileName, 1, 1);

        if (! str_contains($newFileName, '.')) {
            throw new RuntimeException('Portable file mới phải có phần mở rộng.');
        }

        $token = $this->drive->accessToken();
        $parentId = $this->rootFolderId();

        foreach ($folders as $folder) {
            $parentId = $this->findChildFolder($token, $parentId, $folder)
                ?? throw new RuntimeException('Không tìm thấy thư mục portable file trên Google Drive.');
        }

        $file = $this->findChildFile($token, $parentId, $fileName);
        if ($file === null) {
            throw new RuntimeException('Không tìm thấy portable file cần đổi tên trên Google Drive.');
        }

        if ($fileName !== $newFileName && $this->findChildFile($token, $parentId, $newFileName) !== null) {
            throw new RuntimeException('Tên portable file mới đã tồn tại trên Google Drive.');
        }

        $response = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(30)
            ->patch('https://www.googleapis.com/drive/v3/files/'.rawurlencode((string) $file['id']), [
                'name' => $newFileName,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể đổi tên portable file trên Google Drive. HTTP '.$response->status());
        }

        return [
            'id' => (string) $file['id'],
            'old_name' => $fileName,
            'name' => $newFileName,
            'path' => implode('/', array_merge($folders, [$newFileName])),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function delete(string $relativePath): void
    {
        [$folders, $fileName] = $this->splitPath($relativePath);
        $token = $this->drive->accessToken();
        $parentId = $this->rootFolderId();

        foreach ($folders as $folder) {
            $parentId = $this->findChildFolder($token, $parentId, $folder)
                ?? throw new RuntimeException('Không tìm thấy thư mục portable file trên Google Drive.');
        }

        $file = $this->findChildFile($token, $parentId, $fileName);
        if ($file === null) {
            throw new RuntimeException('Không tìm thấy portable file cần xóa trên Google Drive.');
        }

        $response = Http::withToken($token)
            ->timeout(30)
            ->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode((string) $file['id']));

        if (! $response->successful()) {
            throw new RuntimeException('Không thể xóa portable file trên Google Drive. HTTP '.$response->status());
        }
    }

    private function rootFolderId(): string
    {
        $rootId = trim((string) ($this->drive->status()['folder_id'] ?? ''));

        if ($rootId === '') {
            $this->drive->testConnection();
            $rootId = trim((string) ($this->drive->status()['folder_id'] ?? ''));
        }

        if ($rootId === '') {
            throw new RuntimeException('Không xác định được thư mục Laravel-Backup trên Google Drive.');
        }

        return $rootId;
    }

    private function splitPath(string $relativePath): array
    {
        $segments = $this->validatedSegments($relativePath, 2, 8);
        $fileName = array_pop($segments);

        if (! str_contains($fileName, '.')) {
            throw new RuntimeException('Portable file phải có phần mở rộng.');
        }

        return [$segments, $fileName];
    }

    private function splitDirectory(string $relativeDirectory): array
    {
        return $this->validatedSegments($relativeDirectory, 1, 7);
    }

    private function validatedSegments(string $relativePath, int $minimum, int $maximum): array
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $segments = array_values(array_filter(explode('/', $relativePath), static fn (string $segment): bool => $segment !== ''));

        if (count($segments) < $minimum || count($segments) > $maximum) {
            throw new RuntimeException('Portable file path không hợp lệ.');
        }

        foreach ($segments as $segment) {
            if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9_. -]{0,99}\z/u', $segment) || in_array($segment, ['.', '..'], true)) {
                throw new RuntimeException('Portable file path chứa segment không hợp lệ.');
            }
        }

        return $segments;
    }

    private function ensureChildFolder(string $token, string $parentId, string $name): string
    {
        return $this->findChildFolder($token, $parentId, $name)
            ?? $this->createFolder($token, $parentId, $name);
    }

    private function findChildFolder(string $token, string $parentId, string $name): ?string
    {
        $escaped = $this->escapeQueryValue($name);
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "name = '{$escaped}' and mimeType = 'application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed = false",
            'fields' => 'files(id,name)',
            'pageSize' => 10,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể đọc portable folder trên Google Drive. HTTP '.$response->status());
        }

        $id = trim((string) ($response->json('files.0.id') ?? ''));

        return $id !== '' ? $id : null;
    }

    private function createFolder(string $token, string $parentId, string $name): string
    {
        $response = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(30)
            ->post('https://www.googleapis.com/drive/v3/files', [
                'name' => $name,
                'parents' => [$parentId],
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);

        $id = trim((string) $response->json('id'));
        if (! $response->successful() || $id === '') {
            throw new RuntimeException('Không thể tạo portable folder trên Google Drive. HTTP '.$response->status());
        }

        return $id;
    }

    private function findChildFile(string $token, string $parentId, string $name): ?array
    {
        $escaped = $this->escapeQueryValue($name);
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "name = '{$escaped}' and mimeType != 'application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed = false",
            'fields' => 'files(id,name,size,modifiedTime)',
            'pageSize' => 10,
            'orderBy' => 'modifiedTime desc',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể đọc portable file trên Google Drive. HTTP '.$response->status());
        }

        $file = $response->json('files.0');

        return is_array($file) ? $file : null;
    }

    private function escapeQueryValue(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
