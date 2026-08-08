<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Mysql\DatabaseService;
use App\Services\Mysql\BackupService;
use App\Services\Mysql\QueryService;

class MysqlDatabaseCommand extends Command
{
    protected $signature = 'db:mysql
        {action : create|delete|show|query|import|backup|restore}
        {value? : database name, query, or file path}
        {--force : Skip confirmation}';

    protected $description = 'Manage MySQL databases safely';

    public function __construct(
        protected DatabaseService $databaseService,
        protected BackupService $backupService,
        protected QueryService $queryService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('❌ Command này bị khóa trên production.');
            return Command::FAILURE;
        }

        $action = $this->argument('action');
        $value  = $this->argument('value');

        return match ($action) {
            'create'  => $this->databaseService->create($this, $value),
            'delete'  => $this->databaseService->delete($this, $value),
            'show'    => $this->databaseService->list($this),
            'query'   => $this->queryService->run($this, $value),
            'import'  => $this->backupService->import($this, $value),
            'backup'  => $this->backupService->backup($this, $value),
            'restore' => $this->backupService->restore($this, $value),
            default   => $this->helpAndFail(),
        };
    }

    protected function helpAndFail(): int
    {
        $this->line('Usage examples:');
        $this->line(' php artisan db:mysql create my_db');
        $this->line(' php artisan db:mysql delete my_db');
        $this->line(' php artisan db:mysql show');
        $this->newLine();

        $this->line('Query (read-only – allowed by default):');
        $this->line(' php artisan db:mysql query "SHOW TABLES"');
        $this->line(' php artisan db:mysql query "SELECT * FROM users"');
        $this->newLine();

        $this->line('⚠️ Dangerous queries (BLOCKED by default):');
        $this->line(' php artisan db:mysql query "TRUNCATE TABLE users"');
        $this->line(' php artisan db:mysql query "DROP TABLE users"');
        $this->line(' → Cần mở whitelist hoặc chỉnh QueryService');
        $this->newLine();

        $this->line('Backup & Restore:');
        $this->line(' php artisan db:mysql backup storage/db.sql');
        $this->line(' php artisan db:mysql restore storage/db.sql --force');

        return Command::FAILURE;
    }

}
