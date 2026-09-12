<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class ModuleSnapshotDeletionService
{
    public function __construct(private readonly ModuleSnapshotService $snapshots) {}

    public function deleteLocal(string $module, string $reference): array
    {
        $snapshot = $this->snapshots->resolveLocalReference($reference, $module);

        if ($snapshot === null) {
            throw new RuntimeException('Module snapshot local không tồn tại.');
        }

        $path = (string) ($snapshot['absolute_path'] ?? '');

        if ($path === '' || ! is_file($path)) {
            throw new RuntimeException('Module snapshot local không còn tồn tại.');
        }

        $trustedRoot = realpath(storage_path('app/private/backups/modules'));
        $trustedFile = realpath($path);

        if ($trustedRoot === false || $trustedFile === false) {
            throw new RuntimeException('Không thể xác minh vị trí Module Snapshot local.');
        }

        $rootPrefix = rtrim($trustedRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (! str_starts_with($trustedFile, $rootPrefix)) {
            throw new RuntimeException('Module Snapshot nằm ngoài vùng local được phép.');
        }

        if (! unlink($trustedFile)) {
            throw new RuntimeException('Không thể xóa Module Snapshot local.');
        }

        Log::notice('Local module snapshot deleted.', [
            'module' => $module,
            'snapshot' => $snapshot['name'],
            'snapshot_type' => $snapshot['snapshot_type'],
        ]);

        return [
            'name' => $snapshot['name'],
            'module' => $module,
            'snapshot_type' => $snapshot['snapshot_type'],
        ];
    }
}
