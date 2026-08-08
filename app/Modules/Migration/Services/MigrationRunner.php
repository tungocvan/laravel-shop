<?php

namespace App\Modules\Migration\Services;

use App\Modules\Migration\Support\MigrationFile;
use App\Modules\Migration\Guards\PrefixGuard;

class MigrationRunner
{
    public function __construct(
        protected PrefixGuard $guard
    ) {}

    public function run(string $module, MigrationFile $file)
    {
        // 🔥 GUARD CHECK
        $this->guard->check($module, $file->path);

        $migration = $file->require();

        $migration->up();
    }

    public function rollback(MigrationFile $file)
    {
        $migration = $file->require();

        if (method_exists($migration, 'down')) {
            $migration->down();
        }
    }
}
