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

        return [
            'id' => (string) $file['id'],
            'path' => $relativePath,
            'name' => $fileName,
            'size' => $size,
            'modified_at' => $file['modifiedTime'] ?? null,
            'content' => $response->body(),
        ];
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
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $segments = array_values(array_filter(explode('/', $relativePath), static fn (string $segment): bool => $segment !== ''));

        if (count($segments) < 2 || count($segments) > 8) {
            throw new RuntimeException('Portable file path không hợp lệ.');
        }

        foreach ($segments as $segment) {
            if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9_. -]{0,99}\z/u', $segment) || in_array($segment, ['.', '..'], true)) {
                throw new RuntimeException('Portable file path chứa segment không hợp lệ.');
            }
        }

        $fileName = array_pop($segments);

        if (! str_contains($fileName, '.')) {
            throw new RuntimeException('Portable file phải có phần mở rộng.');
        }

        return [$segments, $fileName];
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
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }
}
