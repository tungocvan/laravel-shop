<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Invoices\Mail\InvoiceFilesBackupMail;
use RuntimeException;
use ZipArchive;

class InvoiceFilesEmailBackupService
{
    public function send(string $email): array
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Địa chỉ email nhận backup không hợp lệ.');
        }

        $files = $this->files();
        if ($files === []) {
            throw new RuntimeException('Không có File đã đồng bộ để backup.');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP chưa cài extension zip (ZipArchive).');
        }

        $chunks = $this->chunkFiles($files, (int) config('invoices.backup.email_chunk_bytes', 12 * 1024 * 1024));
        $tempDir = storage_path('app/invoices/backups/email');
        if (! is_dir($tempDir) && ! @mkdir($tempDir, 0775, true) && ! is_dir($tempDir)) {
            throw new RuntimeException('Không thể tạo thư mục backup email. Kiểm tra quyền ghi storage.');
        }

        $sent = 0;
        $totalFiles = 0;
        $totalParts = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $part = $index + 1;
            $zipName = 'invoice-files-backup_'.now()->format('Ymd_His').($totalParts > 1 ? "_part-{$part}-of-{$totalParts}" : '').'.zip';
            $zipPath = $tempDir.DIRECTORY_SEPARATOR.Str::random(8).'_'.$zipName;

            try {
                $this->createZip($zipPath, $chunk);
                Mail::to($email)->send(new InvoiceFilesBackupMail(
                    zipPath: $zipPath,
                    zipName: $zipName,
                    part: $part,
                    totalParts: $totalParts,
                    fileCount: count($chunk),
                ));
                $sent++;
                $totalFiles += count($chunk);
            } finally {
                @unlink($zipPath);
            }
        }

        return ['emails_sent' => $sent, 'files_backed_up' => $totalFiles, 'parts' => $totalParts];
    }

    private function files(): array
    {
        $base = trim((string) config('invoices.storage.export_directory', 'gdt'), '/');
        $result = [];

        foreach (['vat_in', 'vat_out'] as $direction) {
            $folder = storage_path("app/{$base}/{$direction}");
            if (! is_dir($folder)) continue;

            foreach (glob($folder.'/*.{xlsx,csv}', GLOB_BRACE) ?: [] as $path) {
                $name = basename($path);
                $lower = strtolower($name);
                if (($direction === 'vat_in' && ! str_starts_with($lower, 'vat_in_')) || ($direction === 'vat_out' && ! str_starts_with($lower, 'vat_out_'))) {
                    continue;
                }
                if (! is_file($path) || ! is_readable($path)) continue;

                $result[] = ['path' => $path, 'name' => $direction.'/'.$name, 'size' => filesize($path) ?: 0];
            }
        }

        usort($result, fn (array $a, array $b) => strcmp($a['name'], $b['name']));
        return $result;
    }

    private function chunkFiles(array $files, int $maxBytes): array
    {
        $maxBytes = max(1024 * 1024, $maxBytes);
        $chunks = [];
        $current = [];
        $size = 0;

        foreach ($files as $file) {
            if ($current !== [] && $size + $file['size'] > $maxBytes) {
                $chunks[] = $current;
                $current = [];
                $size = 0;
            }
            $current[] = $file;
            $size += $file['size'];
        }

        if ($current !== []) $chunks[] = $current;
        return $chunks;
    }

    private function createZip(string $zipPath, array $files): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không thể tạo file ZIP backup.');
        }

        try {
            foreach ($files as $file) {
                if (! $zip->addFile($file['path'], $file['name'])) {
                    throw new RuntimeException('Không thể thêm file vào ZIP backup: '.basename($file['path']));
                }
            }
        } finally {
            $zip->close();
        }
    }
}
