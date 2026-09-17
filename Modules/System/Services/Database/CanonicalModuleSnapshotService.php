<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

use RuntimeException;
use ZipArchive;

/**
 * Keeps the v1.0 raw fingerprint fast-path, but permits a legacy snapshot when
 * its checksummed module.sql is canonically equivalent to the current schema.
 */
class CanonicalModuleSnapshotService extends ModuleSnapshotService
{
    public function validatePackage(string $path, string $module, bool $enforceSchema = true): array
    {
        // Parent validation always verifies package format, module ownership,
        // table ownership and module.sql checksum before this fallback runs.
        $validated = parent::validatePackage($path, $module, enforceSchema: false);

        if ($validated['compatibility'] !== 'COMPATIBLE') {
            $sql = $this->readVerifiedSql($path);
            $tables = array_values((array) ($validated['manifest']['tables'] ?? []));

            if (app(ModuleSchemaCanonicalizer::class)->legacySqlMatchesCurrent($sql, $tables)) {
                $validated['compatibility'] = 'COMPATIBLE';
                $validated['compatibility_basis'] = 'canonical_schema';
            }
        } else {
            $validated['compatibility_basis'] = 'raw_fingerprint';
        }

        if ($enforceSchema && $validated['compatibility'] !== 'COMPATIBLE') {
            throw new RuntimeException('Schema hiện tại không tương thích với module snapshot đã chọn.');
        }

        return $validated;
    }

    private function readVerifiedSql(string $path): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Không thể mở module snapshot để xác minh schema canonical.');
        }

        try {
            $sql = $zip->getFromName('module.sql');
        } finally {
            $zip->close();
        }

        if (! is_string($sql) || $sql === '') {
            throw new RuntimeException('Module snapshot không có SQL hợp lệ để xác minh schema canonical.');
        }

        return $sql;
    }
}
