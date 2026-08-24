<?php

namespace App\Modules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ModuleMigrationLedgerRepairer
{
    public function repair(ModuleMigrationRecoveryAssessment $assessment): array
    {
        if (! $assessment->isRecoverable() || $assessment->recoverableMigrations === []) {
            throw new RuntimeException('Migration recovery assessment không ở trạng thái RECOVERABLE.');
        }

        if (! Schema::hasTable('migrations')) {
            throw new RuntimeException('Không tìm thấy bảng migrations để phục hồi ledger.');
        }

        return DB::transaction(function () use ($assessment): array {
            $existing = DB::table('migrations')
                ->whereIn('migration', $assessment->recoverableMigrations)
                ->pluck('migration')
                ->map('strval')
                ->all();
            $missing = array_values(array_diff($assessment->recoverableMigrations, $existing));

            if ($missing === []) {
                return [];
            }

            $batch = ((int) DB::table('migrations')->max('batch')) + 1;
            DB::table('migrations')->insert(array_map(
                fn (string $migration): array => [
                    'migration' => $migration,
                    'batch' => $batch,
                ],
                $missing,
            ));

            return $missing;
        });
    }
}
