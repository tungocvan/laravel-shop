<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use RuntimeException;
use ZipArchive;

final class GoogleDriveInvoiceModuleBackupService
{
    private const INVOICES_FOLDER = 'Invoices';

    private const BACKUP_FOLDER = 'Module-Backups';

    private const MIME = 'application/zip';

    private const MAX_DOWNLOAD_BYTES = 100 * 1024 * 1024;

    public function __construct(private readonly GoogleDriveConnectionService $drive) {}

    public function isConnected(): bool
    {
        return (bool) ($this->drive->status()['connected'] ?? false);
    }

    public function uploadSnapshot(string $directory): array
    {
        $archive = $this->buildArchive($directory);
        try {
            [$token, $folderId] = $this->driveContext(true);
            $name = $archive['name'];
            $existing = $this->findFile($token, $folderId, $name);
            if ($existing !== null) {
                if ($existing['checksum'] === '' || ! hash_equals($archive['checksum'], $existing['checksum'])) {
                    throw new RuntimeException('Đã có module backup cùng tên trên Google Drive nhưng checksum không khớp.');
                }

                return ['id' => $existing['id'], 'name' => $name, 'folder' => 'Laravel-Backup/Invoices/Module-Backups', 'checksum' => $archive['checksum'], 'already_exists' => true];
            }
            $create = Http::withToken($token)->asJson()->acceptJson()->timeout(30)->post('https://www.googleapis.com/drive/v3/files', [
                'name' => $name, 'parents' => [$folderId], 'mimeType' => self::MIME,
                'appProperties' => ['sha256' => $archive['checksum'], 'module' => 'Invoices'],
            ]);
            $fileId = trim((string) $create->json('id'));
            if (! $create->successful() || $fileId === '') {
                throw new RuntimeException('Không thể tạo module backup trên Google Drive. HTTP '.$create->status().'.');
            }
            $body = fopen($archive['path'], 'rb');
            if ($body === false) {
                throw new RuntimeException('Không thể đọc archive module backup.');
            }
            try {
                $upload = Http::withToken($token)->withBody($body, self::MIME)->timeout(300)->patch('https://www.googleapis.com/upload/drive/v3/files/'.rawurlencode($fileId).'?uploadType=media');
            } finally {
                fclose($body);
            }
            if (! $upload->successful()) {
                Http::withToken($token)->timeout(20)->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId));
                throw new RuntimeException('Upload module backup lên Google Drive thất bại. HTTP '.$upload->status().'.');
            }

            return ['id' => $fileId, 'name' => $name, 'folder' => 'Laravel-Backup/Invoices/Module-Backups', 'checksum' => $archive['checksum'], 'already_exists' => false];
        } finally {
            @unlink($archive['path']);
        }
    }

    public function files(): array
    {
        if (! $this->isConnected()) {
            return [];
        }
        [$token, $folderId] = $this->driveContext(false);
        $files = [];
        if ($folderId !== null) {
            $parent = $this->escape($folderId);
            $response = Http::withToken($token)->acceptJson()->timeout(30)->get('https://www.googleapis.com/drive/v3/files', [
                'q' => "trashed = false and '{$parent}' in parents", 'spaces' => 'drive',
                'fields' => 'files(id,name,size,modifiedTime,appProperties)', 'orderBy' => 'modifiedTime desc', 'pageSize' => 100,
            ]);
            if (! $response->successful()) {
                throw new RuntimeException('Không thể đọc module backup trên Google Drive. HTTP '.$response->status().'.');
            }
            $files = $response->json('files') ?: [];
        }
        if ($files === []) {
            $fallback = Http::withToken($token)->acceptJson()->timeout(30)->get('https://www.googleapis.com/drive/v3/files', [
                'q' => "trashed = false and appProperties has { key='module' and value='Invoices' }", 'spaces' => 'drive',
                'fields' => 'files(id,name,size,modifiedTime,appProperties,parents)', 'orderBy' => 'modifiedTime desc', 'pageSize' => 100,
            ]);
            if (! $fallback->successful()) {
                throw new RuntimeException('Không thể dò module backup Invoices trên Google Drive. HTTP '.$fallback->status().'.');
            }
            $files = $fallback->json('files') ?: [];
        }

        return collect($files)->filter(fn (array $file): bool => $this->validArchiveName((string) ($file['name'] ?? '')) && (string) ($file['appProperties']['module'] ?? 'Invoices') === 'Invoices')->map(fn (array $file): array => [
            'id' => (string) ($file['id'] ?? ''), 'name' => $this->canonicalArchiveName((string) ($file['name'] ?? '')),
            'drive_name' => (string) ($file['name'] ?? ''), 'size' => (int) ($file['size'] ?? 0),
            'modified_at' => (string) ($file['modifiedTime'] ?? ''), 'checksum' => (string) ($file['appProperties']['sha256'] ?? ''),
        ])->filter(fn (array $file): bool => $file['id'] !== '')->values()->all();
    }

    public function downloadSnapshot(string $fileId, string $fileName): array
    {
        if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $fileId) || ! $this->validArchiveName($fileName)) {
            throw new RuntimeException('Module backup Google Drive không hợp lệ.');
        }
        [$token, $folderId] = $this->driveContext(false);
        if ($folderId === null) {
            throw new RuntimeException('Không tìm thấy thư mục Module-Backups trên Google Drive.');
        }
        $metadata = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId), ['fields' => 'id,name,parents,trashed,size,appProperties']);
        $parents = $metadata->json('parents');
        $remoteName = (string) $metadata->json('name');
        if (! $metadata->successful() || (bool) $metadata->json('trashed') || $this->canonicalArchiveName($remoteName) !== $this->canonicalArchiveName($fileName) || ! is_array($parents) || ! in_array($folderId, $parents, true)) {
            throw new RuntimeException('Module backup không còn thuộc thư mục Google Drive được bảo vệ.');
        }
        $size = (int) ($metadata->json('size') ?? 0);
        if ($size <= 0 || $size > self::MAX_DOWNLOAD_BYTES) {
            throw new RuntimeException('Kích thước module backup Google Drive không hợp lệ.');
        }
        $download = Http::withToken($token)->timeout(300)->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId), ['alt' => 'media']);
        if (! $download->successful() || $download->body() === '' || strlen($download->body()) > self::MAX_DOWNLOAD_BYTES) {
            throw new RuntimeException('Không thể tải module backup từ Google Drive.');
        }
        $expected = (string) ($metadata->json('appProperties.sha256') ?? '');
        if ($expected === '' || ! hash_equals($expected, hash('sha256', $download->body()))) {
            throw new RuntimeException('Checksum module backup trên Google Drive không hợp lệ.');
        }

        return $this->extractArchive($download->body(), $this->canonicalArchiveName($remoteName));
    }

    private function buildArchive(string $directory): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive chưa sẵn sàng để đóng gói module backup.');
        }
        $relative = trim($directory, '/');
        if (! preg_match('#^invoices/module-backups/[A-Za-z0-9_-]+$#', $relative)) {
            throw new RuntimeException('Đường dẫn module snapshot không hợp lệ.');
        }
        $disk = Storage::disk('local');
        foreach (['manifest.json', 'database/invoices.json', 'database/invoice_files.json'] as $required) {
            if (! $disk->exists($relative.'/'.$required)) {
                throw new RuntimeException('Snapshot thiếu file bắt buộc: '.$required);
            }
        }
        $name = 'Invoices-Module-'.basename($relative).'.zip';
        $temp = storage_path('app/invoices/module-backups/.transport-'.$name);
        if (! is_dir(dirname($temp))) {
            mkdir(dirname($temp), 0775, true);
        }
        $zip = new ZipArchive;
        if ($zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không thể tạo ZIP module backup.');
        }
        foreach (['manifest.json', 'database/invoices.json', 'database/invoice_files.json'] as $file) {
            $zip->addFromString($file, $disk->get($relative.'/'.$file));
        }
        $zip->close();

        return ['path' => $temp, 'name' => $name, 'checksum' => hash_file('sha256', $temp)];
    }

    private function extractArchive(string $body, string $fileName): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive chưa sẵn sàng để đọc module backup.');
        }
        $temp = storage_path('app/invoices/module-backups/.download-'.bin2hex(random_bytes(6)).'.zip');
        if (! is_dir(dirname($temp))) {
            mkdir(dirname($temp), 0775, true);
        }
        if (file_put_contents($temp, $body, LOCK_EX) === false) {
            throw new RuntimeException('Không thể ghi module backup tạm thời.');
        }
        $zip = new ZipArchive;
        if ($zip->open($temp) !== true) {
            @unlink($temp);
            throw new RuntimeException('ZIP module backup không đọc được.');
        }
        $allowed = ['manifest.json', 'database/invoices.json', 'database/invoice_files.json'];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (! in_array($zip->getNameIndex($i), $allowed, true)) {
                $zip->close();
                @unlink($temp);
                throw new RuntimeException('ZIP module backup chứa file ngoài contract.');
            }
        }
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        if (! is_array($manifest) || ($manifest['module'] ?? null) !== 'Invoices') {
            $zip->close();
            @unlink($temp);
            throw new RuntimeException('Manifest module backup không hợp lệ.');
        }
        $directory = 'invoices/module-backups/'.preg_replace('/\.zip$/i', '', str_replace('Invoices-Module-', '', $this->canonicalArchiveName($fileName)));
        $disk = Storage::disk('local');
        if ($disk->exists($directory.'/manifest.json')) {
            $zip->close();
            @unlink($temp);
            throw new RuntimeException('Snapshot đã tồn tại ở local; không tải đè.');
        }
        foreach ($allowed as $file) {
            $contents = $zip->getFromName($file);
            if ($contents === false) {
                $zip->close();
                @unlink($temp);
                throw new RuntimeException('ZIP module backup thiếu file bắt buộc.');
            }
            $disk->put($directory.'/'.$file, $contents);
        }
        $zip->close();
        @unlink($temp);

        return ['directory' => $directory, 'manifest' => $manifest];
    }

    private function driveContext(bool $create): array
    {
        $status = $this->drive->testConnection();
        $rootId = trim((string) ($status['folder_id'] ?? ''));
        if ($rootId === '') {
            throw new RuntimeException('Không xác định được thư mục Laravel-Backup trên Google Drive.');
        }
        $token = $this->drive->accessToken();
        $invoices = $this->folder($token, $rootId, self::INVOICES_FOLDER, $create);
        if ($invoices === null) {
            return [$token, null];
        }

        return [$token, $this->folder($token, $invoices, self::BACKUP_FOLDER, $create)];
    }

    private function folder(string $token, string $parentId, string $name, bool $create): ?string
    {
        $parent = $this->escape($parentId);
        $escaped = $this->escape($name);
        $query = "name = '{$escaped}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false and '{$parent}' in parents";
        $list = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', ['q' => $query, 'spaces' => 'drive', 'fields' => 'files(id,name)', 'pageSize' => 10]);
        if (! $list->successful()) {
            throw new RuntimeException('Không thể tìm thư mục '.$name.' trên Google Drive. HTTP '.$list->status().'.');
        }
        $files = $list->json('files');
        if (is_array($files) && isset($files[0]['id'])) {
            return (string) $files[0]['id'];
        }
        if (! $create) {
            return null;
        }
        $response = Http::withToken($token)->asJson()->acceptJson()->timeout(20)->post('https://www.googleapis.com/drive/v3/files', ['name' => $name, 'mimeType' => 'application/vnd.google-apps.folder', 'parents' => [$parentId]]);
        $id = trim((string) $response->json('id'));
        if (! $response->successful() || $id === '') {
            throw new RuntimeException('Không thể tạo thư mục '.$name.' trên Google Drive. HTTP '.$response->status().'.');
        }

        return $id;
    }

    private function findFile(string $token, string $parentId, string $name): ?array
    {
        $parent = $this->escape($parentId);
        $escaped = $this->escape($name);
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', ['q' => "name = '{$escaped}' and trashed = false and '{$parent}' in parents", 'spaces' => 'drive', 'fields' => 'files(id,name,appProperties)', 'pageSize' => 10]);
        if (! $response->successful()) {
            throw new RuntimeException('Không thể kiểm tra module backup trên Google Drive. HTTP '.$response->status().'.');
        }
        $files = $response->json('files');

        return is_array($files) && isset($files[0]) ? ['id' => (string) $files[0]['id'], 'checksum' => (string) ($files[0]['appProperties']['sha256'] ?? '')] : null;
    }

    private function validArchiveName(string $name): bool
    {
        return (bool) preg_match('/\A(?:\.transport-)?Invoices-Module-[A-Za-z0-9_-]+\.zip\z/', basename($name));
    }

    private function canonicalArchiveName(string $name): string
    {
        return preg_replace('/\A\.transport-/', '', basename($name)) ?: basename($name);
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
