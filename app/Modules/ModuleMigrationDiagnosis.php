<?php

namespace App\Modules;

final readonly class ModuleMigrationDiagnosis
{
    public function __construct(
        public array $expectedTables,
        public array $existingTables,
        public array $missingTables,
        public array $migrationFiles,
        public array $recordedMigrations,
        public array $missingMigrationRecords,
        public bool $resumable = false,
    ) {}

    public function isFresh(): bool
    {
        return $this->existingTables === [] && $this->recordedMigrations === [];
    }

    public function isReady(): bool
    {
        return $this->missingTables === [] && $this->missingMigrationRecords === [];
    }

    public function isResumable(): bool
    {
        return $this->resumable && ! $this->isFresh() && ! $this->isReady();
    }

    public function needsRecovery(): bool
    {
        return ! $this->isFresh() && ! $this->isReady() && ! $this->isResumable();
    }

    public function toArray(): array
    {
        return [
            'expected_tables' => $this->expectedTables,
            'existing_tables' => $this->existingTables,
            'missing_tables' => $this->missingTables,
            'migration_files' => $this->migrationFiles,
            'recorded_migrations' => $this->recordedMigrations,
            'missing_migration_records' => $this->missingMigrationRecords,
            'fresh' => $this->isFresh(),
            'ready' => $this->isReady(),
            'resumable' => $this->isResumable(),
            'needs_recovery' => $this->needsRecovery(),
        ];
    }
}
