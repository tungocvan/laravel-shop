<?php

namespace App\Modules\Migration\Services;

use App\Modules\Migration\Support\MigrationFile;
use App\Modules\ModuleLifecycleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ModuleMigrator
{
    public function __construct(
        protected MigrationRepository $repo,
        protected MigrationRunner $runner,
        protected ModuleDependencyResolver $resolver,
        protected ModuleLifecycleManager $lifecycle
    ) {}

    /**
     * Run the safe default migration path using Laravel's canonical migrations ledger.
     *
     * @return array<string, array<string, mixed>>
     */
    public function migrateCanonical(string $module): array
    {
        $results = [];

        foreach ($this->resolver->resolve($module) as $mod) {
            $results[$mod] = $this->lifecycle->migrateIfNeeded($this->moduleDescriptor($mod));
        }

        return $results;
    }

    /**
     * Legacy module_migrations execution path retained for destructive legacy flows.
     */
    public function migrate($module)
    {
        $modules = $this->resolver->resolve($module);

        foreach ($modules as $mod) {
            $this->runModule($mod);
        }
    }

    protected function runModule($module)
    {
        $path = base_path("Modules/{$module}/database/migrations");

        if (! is_dir($path)) {
            return;
        }

        $files = collect(File::files($path))
            ->map(fn ($f) => new MigrationFile($f->getFilename(), $f->getPathname()))
            ->sortBy(fn ($f) => $f->name);

        $ran = $this->repo->getRan($module);
        $batch = $this->repo->getNextBatch();

        foreach ($files as $file) {
            if (in_array($file->name, $ran)) {
                continue;
            }

            $this->runner->run($module, $file);

            $this->repo->log($module, $file->name, $batch);
        }
    }

    public function rollback($module, $step = 1)
    {
        for ($i = 0; $i < $step; $i++) {
            $batch = $this->repo->getLastBatch($module);

            if (! $batch) {
                return;
            }

            $migrations = $this->repo->getMigrationsByBatch($module, $batch);

            foreach ($migrations as $name) {
                $path = base_path("Modules/{$module}/database/migrations/{$name}");

                $file = new MigrationFile($name, $path);

                $this->runner->rollback($file);

                $this->repo->delete($module, $name);
            }
        }
    }

    public function refresh($module)
    {
        $this->rollback($module, 999);
        $this->migrate($module);
    }

    public function fresh($module)
    {
        $this->dropAllTables($module);
        $this->clearTracking($module);
        $this->migrate($module);
    }

    protected function dropAllTables(string $module): void
    {
        if (! $this->databaseExists()) {
            return;
        }

        $prefix = Str::snake($module).'_';

        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_'.DB::getDatabaseName();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($tables as $t) {
            $table = $t->$key;

            if (str_starts_with($table, $prefix)) {
                Schema::drop($table);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    protected function databaseExists(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function clearTracking($module)
    {
        DB::table('module_migrations')
            ->where('module', $module)
            ->delete();
    }

    public function hasPendingMigrations($module)
    {
        $path = base_path("Modules/{$module}/database/migrations");

        if (! is_dir($path)) {
            return false;
        }

        $files = collect(File::files($path))
            ->map(fn ($f) => $f->getFilename());

        $ran = collect($this->repo->getRan($module));

        return $files->diff($ran)->isNotEmpty();
    }

    public function delete($module)
    {
        $this->dropAllTables($module);
        $this->clearTracking($module);
    }

    /** @return array{name: string, path: string} */
    private function moduleDescriptor(string $module): array
    {
        return [
            'name' => $module,
            'path' => base_path("Modules/{$module}"),
        ];
    }
}
