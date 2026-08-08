<?php
namespace Modules\Admin\Services\Env;

use Illuminate\Support\Facades\File;

class EnvBackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups/env');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    public function createBackup(): string
    {
        $filename = '.env.backup_' . date('Ymd_His');
        $dest = "{$this->backupDir}/{$filename}";

        File::copy(base_path('.env'), $dest); //
        return $filename;
    }
}
