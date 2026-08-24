<?php

namespace App\Modules;

class ModuleMigrationRecoveryAssessor
{
    public function __construct(
        private readonly ModuleMigrationOwnershipVerifier $verifier,
    ) {}

    public function assess(array $module, ModuleMigrationDiagnosis $diagnosis): ModuleMigrationRecoveryAssessment
    {
        if ($diagnosis->isFresh()) {
            return new ModuleMigrationRecoveryAssessment('FRESH');
        }

        if ($diagnosis->isReady()) {
            return new ModuleMigrationRecoveryAssessment('READY');
        }

        $verification = $this->verifier->verify($module);
        $recoverable = [];
        $blocked = [];

        foreach ($diagnosis->missingMigrationRecords as $migration) {
            $result = $verification[$migration] ?? null;
            if ($result !== null && ($result['verified'] ?? false) === true) {
                $recoverable[] = $migration;
            } else {
                $blocked[$migration] = $result ?? ['reason' => 'missing_ownership_contract'];
            }
        }

        if ($diagnosis->missingTables !== []) {
            $blocked['schema'] = ['missing_tables' => $diagnosis->missingTables];
        }

        if ($blocked !== [] || $recoverable === []) {
            return new ModuleMigrationRecoveryAssessment('BLOCKED', $recoverable, $blocked);
        }

        return new ModuleMigrationRecoveryAssessment('RECOVERABLE', $recoverable);
    }
}
