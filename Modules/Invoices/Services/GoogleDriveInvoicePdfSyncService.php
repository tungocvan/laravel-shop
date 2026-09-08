<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Http;
use Modules\Invoices\Models\Invoices;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use RuntimeException;

class GoogleDriveInvoicePdfSyncService
{
    private const PDF_FOLDER = 'PDF';

    private const MAX_BYTES = 25 * 1024 * 1024;

    public function __construct(
        private readonly GoogleDriveConnectionService $drive,
        private readonly InvoiceFileService $fileService,
        private readonly InvoiceFileManagerService $fileManager,
    ) {}

    public function isConnected(): bool
    {
        return (bool) ($this->drive->status()['connected'] ?? false);
    }

    public function snapshot(int $year, int $month): array
    {
        $this->assertPeriod($year, $month);

        $invoices = $this->monthInvoices($year, $month);
        $local = [];
        foreach ($invoices as $invoice) {
            if ($this->fileService->existsForInvoice($invoice)) {
                $path = $this->fileService->pdfPathForInvoice($invoice);
                $local[$this->key($invoice)] = [
                    'invoice_id' => (int) $invoice->getKey(),
                    'name' => $this->fileService->filenameForInvoice($invoice),
                    'type' => $invoice->invoice_type === 'purchase' ? 'purchase' : 'sold',
                    'size' => (int) (filesize($path) ?: 0),
                ];
            }
        }

        $driveFiles = $this->isConnected() ? $this->driveMonthFiles($year, $month) : [];
        $upload = [];
        $restore = [];
        $different = [];
        $synced = 0;

        foreach ($invoices as $invoice) {
            $key = $this->key($invoice);
            $localFile = $local[$key] ?? null;
            $driveFile = $driveFiles[$key] ?? null;

            if ($localFile && ! $driveFile) {
                $upload[] = (int) $invoice->getKey();
            } elseif (! $localFile && $driveFile) {
                $restore[] = ['invoice_id' => (int) $invoice->getKey(), 'drive_id' => $driveFile['id']];
            } elseif ($localFile && $driveFile) {
                if ((int) $localFile['size'] > 0 && (int) $driveFile['size'] > 0 && (int) $localFile['size'] !== (int) $driveFile['size']) {
                    $different[] = (int) $invoice->getKey();
                } else {
                    $synced++;
                }
            }
        }

        return [
            'connected' => $this->isConnected(),
            'local' => count($local),
            'drive' => count($driveFiles),
            'synced' => $synced,
            'upload' => $upload,
            'restore' => $restore,
            'different' => $different,
        ];
    }

    public function uploadInvoice(int $invoiceId): string
    {
        $invoice = Invoices::query()->findOrFail($invoiceId);
        if (! $this->fileService->existsForInvoice($invoice)) {
            throw new RuntimeException('PDF local không còn tồn tại.');
        }

        $path = $this->fileService->pdfPathForInvoice($invoice);
        $size = (int) (filesize($path) ?: 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('PDF local rỗng hoặc vượt quá giới hạn 25 MB.');
        }

        [$token, $rootId] = $this->rootContext();
        $folderId = $this->ensureMonthTypeFolder($token, $rootId, $invoice);
        $name = $this->fileService->filenameForInvoice($invoice);
        $existing = $this->findFile($token, $folderId, $name);

        if ($existing !== null) {
            if ((int) ($existing['size'] ?? 0) === $size) {
                return 'existing';
            }

            throw new RuntimeException('PDF đã có trên Google Drive nhưng kích thước khác; không tự ghi đè.');
        }

        $create = Http::withToken($token)->asJson()->acceptJson()->timeout(30)->post('https://www.googleapis.com/drive/v3/files', [
            'name' => $name,
            'parents' => [$folderId],
            'mimeType' => 'application/pdf',
        ]);
        $fileId = trim((string) $create->json('id'));
        if (! $create->successful() || $fileId === '') {
            throw new RuntimeException('Không thể tạo PDF trên Google Drive. HTTP '.$create->status().'.');
        }

        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Không thể mở PDF local.');
        }

        try {
            $upload = Http::withToken($token)->withBody($stream, 'application/pdf')->timeout(300)
                ->patch('https://www.googleapis.com/upload/drive/v3/files/'.rawurlencode($fileId).'?uploadType=media');
        } finally {
            fclose($stream);
        }

        if (! $upload->successful()) {
            Http::withToken($token)->timeout(20)->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId));
            throw new RuntimeException('Upload PDF lên Google Drive thất bại. HTTP '.$upload->status().'.');
        }

        return 'uploaded';
    }

    public function restoreInvoice(int $invoiceId, string $driveId): string
    {
        $invoice = Invoices::query()->findOrFail($invoiceId);
        if ($this->fileService->existsForInvoice($invoice)) {
            return 'existing';
        }

        if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $driveId)) {
            throw new RuntimeException('Google Drive file ID không hợp lệ.');
        }

        [$token, $rootId] = $this->rootContext();
        $folderId = $this->findMonthTypeFolder($token, $rootId, $invoice);
        if ($folderId === null) {
            throw new RuntimeException('Không tìm thấy thư mục PDF tháng trên Google Drive.');
        }

        $name = $this->fileService->filenameForInvoice($invoice);
        $metadata = Http::withToken($token)->acceptJson()->timeout(20)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($driveId), ['fields' => 'id,name,parents,size,trashed,mimeType']);
        $parents = $metadata->json('parents');
        if (! $metadata->successful() || (bool) $metadata->json('trashed') || (string) $metadata->json('name') !== $name || ! is_array($parents) || ! in_array($folderId, $parents, true)) {
            throw new RuntimeException('PDF Google Drive không còn đúng vị trí hoặc metadata đã thay đổi.');
        }

        $size = (int) ($metadata->json('size') ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new RuntimeException('PDF Google Drive rỗng hoặc vượt quá giới hạn 25 MB.');
        }

        $download = Http::withToken($token)->timeout(300)
            ->get('https://www.googleapis.com/drive/v3/files/'.rawurlencode($driveId), ['alt' => 'media']);
        if (! $download->successful() || $download->body() === '') {
            throw new RuntimeException('Không thể tải PDF từ Google Drive. HTTP '.$download->status().'.');
        }

        $target = $this->fileService->targetPdfPathForInvoice($invoice);
        $directory = dirname($target);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục PDF local.');
        }

        $temp = $target.'.part-'.bin2hex(random_bytes(4));
        if (file_put_contents($temp, $download->body(), LOCK_EX) === false || ! @rename($temp, $target)) {
            @unlink($temp);
            throw new RuntimeException('Không thể lưu PDF Google Drive về local.');
        }

        $this->fileManager->recordAvailable($invoice, $target, 'google_drive');

        return 'restored';
    }

    private function driveMonthFiles(int $year, int $month): array
    {
        [$token, $rootId] = $this->rootContext();
        $result = [];
        foreach (['purchase', 'sold'] as $type) {
            $folderId = $this->findPath($token, $rootId, [self::PDF_FOLDER, (string) $year, str_pad((string) $month, 2, '0', STR_PAD_LEFT), $type]);
            if ($folderId === null) {
                continue;
            }

            $parent = $this->escape($folderId);
            $response = Http::withToken($token)->acceptJson()->timeout(30)->get('https://www.googleapis.com/drive/v3/files', [
                'q' => "trashed = false and '{$parent}' in parents",
                'spaces' => 'drive',
                'fields' => 'files(id,name,size,mimeType)',
                'pageSize' => 1000,
            ]);
            if (! $response->successful()) {
                throw new RuntimeException('Không thể đọc PDF trên Google Drive. HTTP '.$response->status().'.');
            }

            foreach ((array) $response->json('files') as $file) {
                $name = (string) ($file['name'] ?? '');
                if ($name !== '' && str_ends_with(strtolower($name), '.pdf')) {
                    $result[$type.'|'.$name] = ['id' => (string) $file['id'], 'size' => (int) ($file['size'] ?? 0)];
                }
            }
        }

        return $result;
    }

    private function monthInvoices(int $year, int $month)
    {
        $start = now()->setDate($year, $month, 1)->startOfMonth()->toDateString();
        $end = now()->setDate($year, $month, 1)->endOfMonth()->toDateString();

        return Invoices::query()->whereBetween('issued_date', [$start, $end])->orderBy('id')->get();
    }

    private function key(Invoices $invoice): string
    {
        $type = $invoice->invoice_type === 'purchase' ? 'purchase' : 'sold';

        return $type.'|'.$this->fileService->filenameForInvoice($invoice);
    }

    private function rootContext(): array
    {
        if (! $this->isConnected()) {
            throw new RuntimeException('Google Drive chưa kết nối. Vui lòng kết nối trước khi đồng bộ PDF.');
        }
        $status = $this->drive->testConnection();
        $rootId = trim((string) ($status['folder_id'] ?? ''));
        if ($rootId === '') {
            throw new RuntimeException('Không xác định được thư mục Laravel-Backup trên Google Drive.');
        }

        return [$this->drive->accessToken(), $rootId];
    }

    private function ensureMonthTypeFolder(string $token, string $rootId, Invoices $invoice): string
    {
        $date = $invoice->issued_date ?: now();
        $type = $invoice->invoice_type === 'purchase' ? 'purchase' : 'sold';

        return $this->ensurePath($token, $rootId, [self::PDF_FOLDER, $date->format('Y'), $date->format('m'), $type]);
    }

    private function findMonthTypeFolder(string $token, string $rootId, Invoices $invoice): ?string
    {
        $date = $invoice->issued_date ?: now();
        $type = $invoice->invoice_type === 'purchase' ? 'purchase' : 'sold';

        return $this->findPath($token, $rootId, [self::PDF_FOLDER, $date->format('Y'), $date->format('m'), $type]);
    }

    private function ensurePath(string $token, string $parentId, array $segments): string
    {
        foreach ($segments as $segment) {
            $found = $this->findFolder($token, $parentId, $segment);
            if ($found === null) {
                $create = Http::withToken($token)->asJson()->acceptJson()->timeout(20)->post('https://www.googleapis.com/drive/v3/files', [
                    'name' => $segment,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$parentId],
                ]);
                $found = trim((string) $create->json('id'));
                if (! $create->successful() || $found === '') {
                    throw new RuntimeException('Không thể tạo thư mục PDF trên Google Drive. HTTP '.$create->status().'.');
                }
            }
            $parentId = $found;
        }

        return $parentId;
    }

    private function findPath(string $token, string $parentId, array $segments): ?string
    {
        foreach ($segments as $segment) {
            $parentId = $this->findFolder($token, $parentId, $segment);
            if ($parentId === null) {
                return null;
            }
        }

        return $parentId;
    }

    private function findFolder(string $token, string $parentId, string $name): ?string
    {
        $parent = $this->escape($parentId);
        $name = $this->escape($name);
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "name = '{$name}' and mimeType = 'application/vnd.google-apps.folder' and trashed = false and '{$parent}' in parents",
            'spaces' => 'drive',
            'fields' => 'files(id,name)',
            'pageSize' => 10,
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('Không thể đọc thư mục PDF trên Google Drive. HTTP '.$response->status().'.');
        }
        $files = $response->json('files');

        return is_array($files) && isset($files[0]['id']) ? (string) $files[0]['id'] : null;
    }

    private function findFile(string $token, string $parentId, string $name): ?array
    {
        $parent = $this->escape($parentId);
        $name = $this->escape($name);
        $response = Http::withToken($token)->acceptJson()->timeout(20)->get('https://www.googleapis.com/drive/v3/files', [
            'q' => "name = '{$name}' and trashed = false and '{$parent}' in parents",
            'spaces' => 'drive',
            'fields' => 'files(id,name,size)',
            'pageSize' => 10,
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('Không thể kiểm tra PDF trên Google Drive. HTTP '.$response->status().'.');
        }
        $files = $response->json('files');

        return is_array($files) && isset($files[0]) ? $files[0] : null;
    }

    private function assertPeriod(int $year, int $month): void
    {
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            throw new RuntimeException('Kỳ dữ liệu PDF không hợp lệ.');
        }
    }

    private function escape(string $value): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }
}
