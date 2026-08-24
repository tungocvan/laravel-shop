<?php

namespace App\Modules;

final readonly class ModuleMigrationRecoveryAssessment
{
    public function __construct(
        public string $status,
        public array $recoverableMigrations = [],
        public array $blockedMigrations = [],
    ) {}

    public function isRecoverable(): bool
    {
        return $this->status === 'RECOVERABLE';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'BLOCKED';
    }
}
