<?php

namespace App\Console\Commands\Module;

use Illuminate\Console\Command;
use App\Modules\Migration\Services\ModuleMigrator;

class RollbackCommand extends Command
{
    protected $signature = 'module:rollback {module} {--step=1}';

    protected $description = 'Rollback module migrations';

    public function handle(ModuleMigrator $migrator)
    {
        $module = $this->argument('module');
        $step = (int) $this->option('step');

        $migrator->rollback($module, $step);

        $this->info("↩️ Rolled back: {$module}");
    }
}
