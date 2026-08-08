<?php

namespace App\Console\Commands\Module;

use Illuminate\Console\Command;
use App\Modules\Migration\Services\ModuleMigrator;

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

        // ❗ Nếu không truyền module → show guide
        if (!$module) {
            $this->showUsage();
            return;
        }

        // 🚫 Production safety
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('❌ Use --force to run in production');
            return;
        }

        // 🧨 DELETE
        if ($this->option('delete')) {
            $this->warn("🧨 Deleting all tables of module: {$module}");
            $migrator->delete($module);
            $this->info("🗑️ Deleted: {$module}");
            return;
        }

        // 🔥 FRESH
        if ($this->option('fresh')) {
            $this->info("🔥 Fresh module: {$module}");
            $migrator->fresh($module);
            return;
        }

        // 🔄 REFRESH
        if ($this->option('refresh')) {
            $this->info("🔄 Refresh module: {$module}");
            $migrator->refresh($module);
            return;
        }

        // ✅ DEFAULT
        if (!$migrator->hasPendingMigrations($module)) {
            $this->warn("⚠️ Nothing to migrate for {$module}");
            return;
        }

        $migrator->migrate($module);

        $this->info("✅ Migrated: {$module}");
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
