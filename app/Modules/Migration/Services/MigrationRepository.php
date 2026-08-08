<?php

namespace App\Modules\Migration\Services;

use Illuminate\Support\Facades\DB;

class MigrationRepository
{
    protected string $table = 'module_migrations';

    public function getRan($module)
    {
        return DB::table($this->table)
            ->where('module', $module)
            ->pluck('migration')
            ->toArray();
    }

    public function getNextBatch()
    {
        return DB::table($this->table)->max('batch') + 1;
    }

    public function log($module, $migration, $batch)
    {
        DB::table($this->table)->insert([
            'module' => $module,
            'migration' => $migration,
            'batch' => $batch,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function delete($module, $migration)
    {
        DB::table($this->table)
            ->where('module', $module)
            ->where('migration', $migration)
            ->delete();
    }

    public function getLastBatch($module)
    {
        return DB::table($this->table)
            ->where('module', $module)
            ->max('batch');
    }

    public function getMigrationsByBatch($module, $batch)
    {
        return DB::table($this->table)
            ->where('module', $module)
            ->where('batch', $batch)
            ->orderByDesc('id')
            ->pluck('migration')
            ->toArray();
    }
}
