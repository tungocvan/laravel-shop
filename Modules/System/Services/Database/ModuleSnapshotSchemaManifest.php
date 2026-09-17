<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

/**
 * Small value boundary for additive schema metadata. Keeping this separate
 * allows manifest v1.0 readers to ignore the field safely.
 */
final class ModuleSnapshotSchemaManifest
{
    public static function attach(array $manifest, array $schemaManifest): array
    {
        $manifest['schema_manifest'] = $schemaManifest;

        return $manifest;
    }
}
