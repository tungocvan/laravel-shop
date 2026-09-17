<?php

declare(strict_types=1);

namespace Modules\System\Services\Database;

/**
 * Builds additive snapshot metadata without changing restore semantics.
 * ModuleSnapshotService can opt into this service when the package format is
 * extended; old 1.0 manifests remain readable by the Doctor.
 */
class ModuleSnapshotManifestService
{
    public function __construct(private readonly ModuleSchemaDoctorService $doctor) {}

    public function schemaManifest(array $tables): array
    {
        return $this->doctor->currentSchema($tables);
    }
}
