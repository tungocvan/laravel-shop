<?php

namespace Modules\System\Services\Cloud;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDriveBackupBrowserService
{
    public function __construct(private readonly GoogleDriveConnectionService $drive) {}

    public function listBackups(int $limit = 100): array
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
        foreach ($this->listFolders($token, $databaseId) as $year) {
            foreach ($this->listFolders($token, $year['id']) as $month) {
                foreach ($this->listFiles($token, $month['id']) as $file) {
                    $files[] = [
                        'id' => $file['id'],
                        'name' => $file['name'],
                        'size' => (int) ($file['size'] ?? 0),
                        'created_at' => $file['createdTime'] ?? null,
                        'modified_at' => $file['modifiedTime'] ?? null,
                        'year' => $year['name'],
                        'month' => $month['name'],
                        'url' => 'https://drive.google.com/file/d/'.$file['id'].'/view',
                    ];
                }
            }
        }

        usort($files, static fn (array $a, array $b): int => strcmp((string) ($b['modified_at'] ?? ''), (string) ($a['modified_at'] ?? '')));

        return array_slice($files, 0, max(1, $limit));
    }

    public function download(string $fileId, string $destination): void
    {
        if (! preg_match('/^[A-Za-z0-9_-]{10,}$/', $fileId)) {
            throw new RuntimeException('Google Drive file ID không hợp lệ.');
        }

        $response = Http::withToken($this->drive->accessToken())
            ->withOptions(['sink' => $destination])
            ->connectTimeout(20)
            ->timeout(300)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId), ['alt' => 'media']);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể tải backup từ Google Drive. HTTP '.$response->status());
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

    private function listFolders(string $token, string $parentId): array
    {
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "mimeType = 'application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed = false",
            'fields' => 'files(id,name)',
            'pageSize' => 100,
            'orderBy' => 'name desc',
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('Không thể đọc cây thư mục Google Drive. HTTP '.$response->status());
        }

        return is_array($response->json('files')) ? $response->json('files') : [];
    }

    private function listFiles(string $token, string $parentId): array
    {
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "mimeType != 'application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed = false",
            'fields' => 'files(id,name,size,createdTime,modifiedTime)',
            'pageSize' => 100,
            'orderBy' => 'modifiedTime desc',
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('Không thể lấy danh sách backup Google Drive. HTTP '.$response->status());
        }

        return is_array($response->json('files')) ? $response->json('files') : [];
    }
}
