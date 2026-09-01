<?php

namespace App\Console\Commands\Module;

use App\Modules\Migration\Services\ModuleMigrator;
use Illuminate\Console\Command;

class MigrateCommand extends Command
{
    protected $signature = '
        module:migrate
        {module}
        {--refresh}
        {--fresh}
        {--delete}
        {--force}
    ';

    protected $description = 'Run module migrations';

    public function handle(ModuleMigrator $migrator)
    {
        $module = $this->argument('module');

        if (! $module) {
            $this->showUsage();

            return;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('❌ Use --force to run in production');

            return;
        }

        if ($this->option('delete')) {
            $this->warn("🧨 Deleting all tables of module: {$module}");
            $migrator->delete($module);
            $this->info("🗑️ Deleted: {$module}");

            return;
        }

        if ($this->option('fresh')) {
            $this->info("🔥 Fresh module: {$module}");
            $migrator->fresh($module);

            return;
        }

        if ($this->option('refresh')) {
            $this->info("🔄 Refresh module: {$module}");
            $migrator->refresh($module);

            return;
        }

        $results = $migrator->migrateCanonical((string) $module);
        $migrated = collect($results)
            ->filter(fn (array $result): bool => (bool) ($result['migrated'] ?? false))
            ->keys()
            ->values()
            ->all();

        if ($migrated === []) {
            $this->warn("⚠️ Nothing to migrate for {$module}");

            return;
        }

        $this->info('✅ Migrated: '.implode(', ', $migrated));
    }

    protected function showUsage()
    {
        $this->line('');
        $this->info('🚀 Module Migration CLI');
        $this->line('');

        $this->line('Usage:');
        $this->line('  php artisan module:migrate {module} [options]');
        $this->line('');

        $this->line('Examples:');
        $this->line('  php artisan module:migrate Blog');
        $this->line('  php artisan module:migrate Blog --refresh');
        $this->line('  php artisan module:migrate Blog --fresh');
        $this->line('  php artisan module:migrate Blog --delete --force');
        $this->line('');

        $this->line('Options:');
        $this->line('  --refresh   Rollback then migrate');
        $this->line('  --fresh     Drop tables then migrate');
        $this->line('  --delete    Drop tables only (no migrate)');
        $this->line('  --force     Run in production');
        $this->line('');

        $this->warn('⚠️ Note: --delete and --fresh are destructive operations');
        $this->line('');
    }
}
