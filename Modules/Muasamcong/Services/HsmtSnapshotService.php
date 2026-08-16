<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
use RuntimeException;

class HsmtSnapshotService
{
    public function store(string $notifyNo, array $payload): array
    {
        $notifyNo = $this->sanitizeNotifyNo($notifyNo);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        if ($items === []) {
            throw new RuntimeException('Danh mục HSMT không có dữ liệu để lưu snapshot.');
        }

        $directory = 'muasamcong/hsmt/'.$notifyNo;
        $jsonPath = $directory.'/catalogue.json';
        $excelPath = $directory.'/catalogue.xlsx';
        $metadataPath = $directory.'/metadata.json';

        $snapshot = [
            'notify_no' => $notifyNo,
            'notify_id' => $payload['notify_id'] ?? null,
            'process_apply' => $payload['process_apply'] ?? 'LDT',
            'total' => count($items),
            'investor_code' => $payload['investor_code'] ?? null,
            'investor_name' => $payload['investor_name'] ?? null,
            'procuring_entity_code' => $payload['procuring_entity_code'] ?? null,
            'procuring_entity_name' => $payload['procuring_entity_name'] ?? null,
            'form_code' => $payload['form_code'] ?? null,
            'items' => $items,
        ];

        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if (! is_string($json)) {
            throw new RuntimeException('Không thể mã hóa snapshot HSMT.');
        }

        Storage::disk('local')->put($jsonPath, $json);

        $absoluteExcelPath = Storage::disk('local')->path($excelPath);
        $parent = dirname($absoluteExcelPath);
        if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
            throw new RuntimeException('Không thể tạo thư mục lưu Excel HSMT.');
        }

        (new FastExcel($items))->export($absoluteExcelPath);

        $checksum = hash('sha256', $json);
        $metadata = [
            'notify_no' => $notifyNo,
            'notify_id' => $snapshot['notify_id'],
            'total' => count($items),
            'checksum' => $checksum,
            'json_path' => $jsonPath,
            'excel_path' => $excelPath,
            'synced_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put(
            $metadataPath,
            json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $metadata;
    }

    public function load(?string $jsonPath): ?array
    {
        $jsonPath = trim((string) $jsonPath);
        if ($jsonPath === '' || ! Storage::disk('local')->exists($jsonPath)) {
            return null;
        }

        $decoded = json_decode(Storage::disk('local')->get($jsonPath), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function exists(?string $jsonPath): bool
    {
        $jsonPath = trim((string) $jsonPath);

        return $jsonPath !== '' && Storage::disk('local')->exists($jsonPath);
    }

    private function sanitizeNotifyNo(string $notifyNo): string
    {
        $notifyNo = trim($notifyNo);
        if ($notifyNo === '' || preg_match('/^[A-Za-z0-9_-]+$/', $notifyNo) !== 1) {
            throw new RuntimeException('Mã TBMT không hợp lệ để lưu snapshot HSMT.');
        }

        return $notifyNo;
    }
}
