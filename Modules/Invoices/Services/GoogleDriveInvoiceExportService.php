<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Http;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use RuntimeException;

class GoogleDriveInvoiceExportService
{
    private const FOLDER_NAME = 'Invoices';

    private const FOLDER_PATH = 'Laravel-Backup/Invoices';

    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(private readonly GoogleDriveConnectionService $drive) {}

    public function isConnected(): bool
    {
        return (bool) ($this->drive->status()['connected'] ?? false);
    }

    public function upload(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File hóa đơn local không tồn tại hoặc không đọc được.');
        }

        $fileName = basename($path);

        if (! preg_match('/\A(?:vat_in|vat_out)_[A-Za-z0-9_.-]+\.xlsx\z/i', $fileName)) {
            throw new RuntimeException('Tên file Excel hóa đơn không hợp lệ.');
        }

        $status = $this->drive->testConnection();
        $rootId = trim((string) ($status['folder_id'] ?? ''));

        if ($rootId === '') {
            throw new RuntimeException('Không xác định được thư mục Laravel-Backup trên Google Drive.');
        }

        $token = $this->drive->accessToken();
        $folderId = $this->findOrCreateFolder($token, $rootId, self::FOLDER_NAME);
        $fileId = $this->findFile($token, $folderId, $fileName);
        $created = false;

        if ($fileId === null) {
            $fileId = $this->createFile($token, $folderId, $fileName);
            $created = true;
        }

        $stream = fopen($path, 'rb');

        if ($stream === false) {
            if ($created) {
                $this->deleteFile($token, $fileId);
            }

            throw new RuntimeException('Không thể mở file Excel hóa đơn để upload.');
        }

        try {
            $upload = Http::withToken($token)
                ->withBody($stream, self::XLSX_MIME)
                ->timeout(300)
                ->patch('https://www.googleapis.com/upload/drive/v3/files/'.rawurlencode($fileId).'?uploadType=media');
        } finally {
            fclose($stream);
        }

        if (! $upload->successful()) {
            if ($created) {
                $this->deleteFile($token, $fileId);
            }

            throw new RuntimeException('Upload file hóa đơn lên Google Drive thất bại. HTTP '.$upload->status().'.');
        }

        return [
            'id' => $fileId,
            'name' => $fileName,
            'folder' => self::FOLDER_PATH,
            'updated_existing' => ! $created,
        ];
    }

    private function findOrCreateFolder(string $token, string $parentId, string $name): string
    {
        $escaped = $this->escapeQueryValue($name);
        $parent = $this->escapeQueryValue($parentId);
        $query = "name = '{$escaped}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false and '{$parent}' in parents";

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
            throw new RuntimeException('Không thể tìm thư mục Invoices trên Google Drive. HTTP '.$list->status().'.');
        }

        $files = $list->json('files');
        if (is_array($files) && isset($files[0]['id'])) {
            return (string) $files[0]['id'];
        }

        $create = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(20)
            ->post('https://www.googleapis.com/drive/v3/files', [
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentId],
            ]);

        $folderId = trim((string) $create->json('id'));
        if (! $create->successful() || $folderId === '') {
            throw new RuntimeException('Không thể tạo thư mục Invoices trên Google Drive. HTTP '.$create->status().'.');
        }

        return $folderId;
    }

    private function findFile(string $token, string $parentId, string $fileName): ?string
    {
        $escaped = $this->escapeQueryValue($fileName);
        $parent = $this->escapeQueryValue($parentId);
        $query = "name = '{$escaped}' and trashed = false and '{$parent}' in parents";

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
            throw new RuntimeException('Không thể kiểm tra file hóa đơn trên Google Drive. HTTP '.$list->status().'.');
        }

        $files = $list->json('files');

        return is_array($files) && isset($files[0]['id'])
            ? (string) $files[0]['id']
            : null;
    }

    private function createFile(string $token, string $parentId, string $fileName): string
    {
        $create = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(30)
            ->post('https://www.googleapis.com/drive/v3/files', [
                'name' => $fileName,
                'parents' => [$parentId],
                'mimeType' => self::XLSX_MIME,
            ]);

        $fileId = trim((string) $create->json('id'));
        if (! $create->successful() || $fileId === '') {
            throw new RuntimeException('Không thể tạo file hóa đơn trên Google Drive. HTTP '.$create->status().'.');
        }

        return $fileId;
    }

    private function deleteFile(string $token, string $fileId): void
    {
        Http::withToken($token)
            ->timeout(20)
            ->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId));
    }

    private function escapeQueryValue(string $value): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }
}
