<?php

declare(strict_types=1);

namespace Modules\System\Tests\Unit;

use Modules\System\Services\Database\ModuleSnapshotSchemaManifest;
use PHPUnit\Framework\TestCase;

class ModuleSnapshotSchemaManifestTest extends TestCase
{
    public function test_schema_manifest_is_additive_and_keeps_v1_fields(): void
    {
        $manifest = ['format_version' => '1.0', 'module' => 'Pharma'];
        $schema = ['pharma_medicines' => ['columns' => []]];

        $result = ModuleSnapshotSchemaManifest::attach($manifest, $schema);

        self::assertSame('1.0', $result['format_version']);
        self::assertSame('Pharma', $result['module']);
        self::assertSame($schema, $result['schema_manifest']);
    }
}
