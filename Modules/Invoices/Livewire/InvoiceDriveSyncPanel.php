<?php

namespace Modules\Invoices\Livewire;

use Livewire\Component;
use Modules\Invoices\Services\GoogleDriveInvoiceFileSyncService;
use RuntimeException;

class InvoiceDriveSyncPanel extends Component
{
    protected GoogleDriveInvoiceFileSyncService $driveSync;

    public array $localFiles = [];
    public array $driveFiles = [];
    public ?string $selectedLocalFile = null;
    public ?string $selectedDriveFile = null;
    public bool $driveConnected = false;
    public ?string $notice = null;
    public ?string $error = null;

    public function boot(GoogleDriveInvoiceFileSyncService $driveSync): void
    {
        $this->driveSync = $driveSync;
    }

    public function mount(): void
    {
        $this->refreshFiles();
    }

    public function refreshFiles(): void
    {
        $this->authorizeAccess();
        $this->notice = null;
        $this->error = null;
        $this->localFiles = $this->loadLocalFiles();
        $this->driveConnected = $this->driveSync->isConnected();

        try {
            $this->driveFiles = $this->driveConnected ? $this->driveSync->files() : [];
        } catch (\Throwable $exception) {
            $this->driveFiles = [];
            $this->error = $exception->getMessage();
        }

        if ($this->selectedLocalFile && ! collect($this->localFiles)->contains('token', $this->selectedLocalFile)) {
            $this->selectedLocalFile = null;
        }

        if ($this->selectedDriveFile && ! collect($this->driveFiles)->contains('id', $this->selectedDriveFile)) {
            $this->selectedDriveFile = null;
        }
    }

    public function uploadSelectedToDrive(): void
    {
        $this->authorizeAccess();
        $this->notice = null;
        $this->error = null;

        if (! $this->driveSync->isConnected()) {
            $this->error = 'Google Drive chưa được kết nối.';

            return;
        }

        try {
            [, $filename, $path] = $this->resolveLocalSelection();
            $result = $this->driveSync->uploadLocalFile($path);
            $this->notice = ($result['updated_existing'] ?? false)
                ? 'Đã cập nhật file '.$filename.' trên Google Drive.'
                : 'Đã upload '.$filename.' lên Laravel-Backup/Invoices.';
            $this->refreshAfterAction();
        } catch (\Throwable $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function downloadSelectedFromDrive(): void
    {
        $this->authorizeAccess();
        $this->notice = null;
        $this->error = null;

        try {
            $driveFile = collect($this->driveFiles)
                ->firstWhere('id', (string) $this->selectedDriveFile);

            if (! is_array($driveFile)) {
                throw new RuntimeException('Vui lòng chọn file trên Google Drive.');
            }

            $filename = basename((string) ($driveFile['name'] ?? ''));
            $direction = $this->directionFromFilename($filename);
            if ($direction === null) {
                throw new RuntimeException('Tên file Google Drive không đúng quy ước vat_in_* hoặc vat_out_*.');
            }

            $folder = $this->syncFolder($direction);
            $target = $folder.DIRECTORY_SEPARATOR.$filename;

            if (is_file($target)) {
                $this->notice = 'File '.$filename.' đã có ở local; không tải đè.';

                return;
            }

            $this->driveSync->downloadToLocal((string) $driveFile['id'], $filename, $target);
            $this->notice = 'Đã đồng bộ '.$filename.' từ Google Drive về local.';
            $this->refreshAfterAction();
        } catch (\Throwable $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function render()
    {
        return view('Invoices::livewire.invoice-drive-sync-panel');
    }

    private function refreshAfterAction(): void
    {
        $notice = $this->notice;
        $this->localFiles = $this->loadLocalFiles();
        $this->driveConnected = $this->driveSync->isConnected();
        $this->driveFiles = $this->driveConnected ? $this->driveSync->files() : [];
        $this->notice = $notice;
    }

    private function loadLocalFiles(): array
    {
        $files = [];

        foreach (['vat_out' => 'Bán ra', 'vat_in' => 'Mua vào'] as $direction => $label) {
            $folder = $this->syncFolder($direction);
            if (! is_dir($folder)) {
                continue;
            }

            foreach (glob($folder.'/*.xlsx') ?: [] as $path) {
                $filename = basename($path);
                if ($this->directionFromFilename($filename) !== $direction) {
                    continue;
                }

                $files[] = [
                    'token' => $direction.'|'.$filename,
                    'name' => $filename,
                    'direction' => $direction,
                    'type_label' => $label,
                    'size' => filesize($path) ?: 0,
                    'modified_at' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
                    'mtime' => filemtime($path) ?: 0,
                ];
            }
        }

        usort($files, fn (array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

        return array_map(function (array $file): array {
            unset($file['mtime']);

            return $file;
        }, array_slice($files, 0, 100));
    }

    private function resolveLocalSelection(): array
    {
        if (! is_string($this->selectedLocalFile) || $this->selectedLocalFile === '') {
            throw new RuntimeException('Vui lòng chọn file local.');
        }

        [$direction, $filename] = array_pad(explode('|', $this->selectedLocalFile, 2), 2, null);

        if (! in_array($direction, ['vat_in', 'vat_out'], true) || ! is_string($filename) || basename($filename) !== $filename) {
            throw new RuntimeException('File local đã chọn không hợp lệ.');
        }

        if ($this->directionFromFilename($filename) !== $direction) {
            throw new RuntimeException('Tên file local không khớp loại hóa đơn.');
        }

        $path = $this->syncFolder($direction).DIRECTORY_SEPARATOR.$filename;
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File local không tồn tại hoặc không đọc được.');
        }

        return [$direction, $filename, $path];
    }

    private function directionFromFilename(string $filename): ?string
    {
        $name = strtolower(basename(trim($filename)));

        if (str_starts_with($name, 'vat_in_')) {
            return 'vat_in';
        }

        if (str_starts_with($name, 'vat_out_')) {
            return 'vat_out';
        }

        return null;
    }

    private function syncFolder(string $direction): string
    {
        $base = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');

        return storage_path("app/{$base}/{$direction}");
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can('invoices-create'), 403);
    }
}
