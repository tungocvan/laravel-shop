<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class InvoiceModuleSnapshotService
{
    public const FORMAT_VERSION = 1;

    public function create(string $mode = 'manual'): array
    {
        $createdAt = now();
        $stamp = $createdAt->format('Ymd_His');
        $directory = 'invoices/module-backups/'.$stamp;

        $invoices = DB::table('invoices')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $files = DB::table('invoice_files')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $payloads = [
            'database/invoices.json' => $this->json($invoices),
            'database/invoice_files.json' => $this->json($files),
        ];

        $checksums = [];
        foreach ($payloads as $path => $contents) {
            Storage::disk('local')->put($directory.'/'.$path, $contents);
            $checksums[$path] = hash('sha256', $contents);
        }

        $manifest = [
            'module' => 'Invoices',
            'format_version' => self::FORMAT_VERSION,
            'mode' => $mode,
            'created_at' => $createdAt->toIso8601String(),
            'tables' => [
                'invoices' => count($invoices),
                'invoice_files' => count($files),
            ],
            'partner_master_included' => false,
            'pdf_binaries_included' => false,
            'checksums' => $checksums,
        ];

        $manifestContents = $this->json($manifest);
        Storage::disk('local')->put($directory.'/manifest.json', $manifestContents);

        return [
            'directory' => $directory,
            'manifest' => $manifest,
            'manifest_checksum' => hash('sha256', $manifestContents),
        ];
    }

    public function inspect(string $directory): array
    {
        $disk = Storage::disk('local');
        $manifestPath = trim($directory, '/').'/manifest.json';
        if (! $disk->exists($manifestPath)) {
            throw new RuntimeException('Không tìm thấy manifest snapshot Invoices.');
        }

        $manifest = json_decode($disk->get($manifestPath), true);
        $manifestValid = is_array($manifest)
            && ($manifest['module'] ?? null) === 'Invoices'
            && isset($manifest['checksums'])
            && is_array($manifest['checksums']);
        $checksumValid = $manifestValid;

        if ($manifestValid) {
            foreach ($manifest['checksums'] as $path => $expected) {
                $fullPath = trim($directory, '/').'/'.$path;
                if (! $disk->exists($fullPath) || ! hash_equals((string) $expected, hash('sha256', $disk->get($fullPath)))) {
                    $checksumValid = false;
                    break;
                }
            }
        }

        return [
            'manifest' => $manifestValid ? $manifest : [],
            'manifest_valid' => $manifestValid,
            'checksum_valid' => $checksumValid,
            'version_supported' => $manifestValid && (int) ($manifest['format_version'] ?? 0) === self::FORMAT_VERSION,
        ];
    }

    public function readTable(string $directory, string $table): array
    {
        if (! in_array($table, ['invoices', 'invoice_files'], true)) {
            throw new RuntimeException('Bảng snapshot Invoices không được hỗ trợ.');
        }

        $inspection = $this->inspect($directory);
        if (! $inspection['manifest_valid'] || ! $inspection['checksum_valid'] || ! $inspection['version_supported']) {
            throw new RuntimeException('Snapshot Invoices chưa vượt qua kiểm tra integrity.');
        }

        $path = trim($directory, '/').'/database/'.$table.'.json';
        $payload = json_decode(Storage::disk('local')->get($path), true);
        if (! is_array($payload)) {
            throw new RuntimeException('Payload snapshot Invoices không hợp lệ.');
        }

        return $payload;
    }

    private function json(array $value): string
    {
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Không thể tạo payload snapshot Invoices.');
        }

        return $json;
    }
}
