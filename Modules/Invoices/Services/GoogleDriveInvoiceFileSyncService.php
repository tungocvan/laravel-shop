<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Http;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use RuntimeException;

class GoogleDriveInvoiceFileSyncService
{
    private const FOLDER_NAME = 'Invoices';

    public function __construct(
        private readonly GoogleDriveConnectionService $drive,
        private readonly GoogleDriveInvoiceExportService $exportService,
    ) {}

    public function isConnected(): bool
    {
        return $this->exportService->isConnected();
    }

    public function uploadLocalFile(string $path): array
    {
        return $this->exportService->upload($path);
    }

    public function files(): array
    {
        if (! $this->isConnected()) {
            return [];
        }

        [$token, $folderId] = $this->driveContext();

        if ($folderId === null) {
            return [];
        }

        $parent = $this->escapeQueryValue($folderId);
        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => "trashed = false and '{$parent}' in parents",
                'spaces' => 'drive',
                'fields' => 'files(id,name,size,modifiedTime,mimeType)',
                'orderBy' => 'modifiedTime desc',
                'pageSize' => 100,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể đọc danh sách file hóa đơn trên Google Drive. HTTP '.$response->status().'.');
        }

        $files = $response->json('files');

        if (! is_array($files)) {
            return [];
        }

        return collect($files)
            ->filter(fn (array $file): bool => $this->validFileName((string) ($file['name'] ?? '')))
            ->map(fn (array $file): array => [
                'id' => (string) ($file['id'] ?? ''),
                'name' => (string) ($file['name'] ?? ''),
                'size' => (int) ($file['size'] ?? 0),
                'modified_at' => (string) ($file['modifiedTime'] ?? ''),
                'direction' => str_starts_with(strtolower((string) ($file['name'] ?? '')), 'vat_in_') ? 'vat_in' : 'vat_out',
            ])
            ->filter(fn (array $file): bool => $file['id'] !== '' && $file['name'] !== '')
            ->values()
            ->all();
    }

    public function downloadToLocal(string $fileId, string $fileName, string $targetPath): array
    {
        if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $fileId)) {
            throw new RuntimeException('Google Drive file ID không hợp lệ.');
        }

        if (! $this->validFileName($fileName)) {
            throw new RuntimeException('Tên file Google Drive không hợp lệ.');
        }

        if (is_file($targetPath)) {
            throw new RuntimeException('File đã tồn tại ở local; không tải đè.');
        }

        [$token, $folderId] = $this->driveContext();

        if ($folderId === null) {
            throw new RuntimeException('Không tìm thấy thư mục Laravel-Backup/Invoices trên Google Drive.');
        }

        $metadata = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId), [
                'fields' => 'id,name,parents,mimeType,trashed,size',
            ]);

        if (! $metadata->successful()) {
            throw new RuntimeException('Không thể xác minh file Google Drive. HTTP '.$metadata->status().'.');
        }

        $parents = $metadata->json('parents');
        if (
            (bool) $metadata->json('trashed')
            || (string) $metadata->json('name') !== $fileName
            || ! is_array($parents)
            || ! in_array($folderId, $parents, true)
        ) {
            throw new RuntimeException('File Google Drive không thuộc thư mục Laravel-Backup/Invoices hoặc metadata đã thay đổi.');
        }

        $size = (int) ($metadata->json('size') ?? 0);
        if ($size > 20 * 1024 * 1024) {
            throw new RuntimeException('File Google Drive vượt quá giới hạn 20 MB.');
        }

        $download = Http::withToken($token)
            ->timeout(300)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId), [
                'alt' => 'media',
            ]);

        if (! $download->successful()) {
            throw new RuntimeException('Không thể tải file hóa đơn từ Google Drive. HTTP '.$download->status().'.');
        }

        $body = $download->body();
        if ($body === '' || strlen($body) > 20 * 1024 * 1024) {
            throw new RuntimeException('Nội dung file Google Drive rỗng hoặc vượt quá giới hạn 20 MB.');
        }

        $directory = dirname($targetPath);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục local để nhận file Google Drive.');
        }

        $temp = $targetPath.'.part-'.bin2hex(random_bytes(4));
        if (file_put_contents($temp, $body, LOCK_EX) === false || ! @rename($temp, $targetPath)) {
            @unlink($temp);
            throw new RuntimeException('Không thể lưu file Google Drive về local.');
        }

        return [
            'id' => $fileId,
            'name' => $fileName,
            'path' => $targetPath,
            'size' => filesize($targetPath) ?: strlen($body),
        ];
    }

    private function driveContext(): array
    {
        $status = $this->drive->testConnection();
        $rootId = trim((string) ($status['folder_id'] ?? ''));

        if ($rootId === '') {
            throw new RuntimeException('Không xác định được thư mục Laravel-Backup trên Google Drive.');
        }

        $token = $this->drive->accessToken();
        $folderId = $this->findFolder($token, $rootId);

        return [$token, $folderId];
    }

    private function findFolder(string $token, string $rootId): ?string
    {
        $name = $this->escapeQueryValue(self::FOLDER_NAME);
        $parent = $this->escapeQueryValue($rootId);
        $query = "name = '{$name}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false and '{$parent}' in parents";

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => $query,
                'spaces' => 'drive',
                'fields' => 'files(id,name)',
                'pageSize' => 10,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Không thể tìm thư mục Invoices trên Google Drive. HTTP '.$response->status().'.');
        }

        $files = $response->json('files');

        return is_array($files) && isset($files[0]['id'])
            ? (string) $files[0]['id']
            : null;
    }

    private function validFileName(string $fileName): bool
    {
        return (bool) preg_match('/\A(?:vat_in|vat_out)_[A-Za-z0-9_.-]+\.xlsx\z/i', basename($fileName));
    }

    private function escapeQueryValue(string $value): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }
}
