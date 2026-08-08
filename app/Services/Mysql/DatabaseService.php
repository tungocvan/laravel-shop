<?php

namespace App\Services\Mysql;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseService
{
    public function create(Command $cmd, ?string $name): int
    {
        if (!$name) {
            $cmd->error('Database name is required.');
            return Command::FAILURE;
        }

        if ($this->exists($name)) {
            $cmd->warn("Database [$name] already exists.");
            return Command::SUCCESS;
        }

        DB::statement("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $cmd->info("✅ Database [$name] created.");

        return Command::SUCCESS;
    }

    public function delete(Command $cmd, ?string $name): int
    {
        if (!$name) {
            $cmd->error('Database name is required.');
            return Command::FAILURE;
        }

        if (!$this->exists($name)) {
            $cmd->warn("Database [$name] does not exist.");
            return Command::SUCCESS;
        }

        if (!$cmd->option('force') &&
            !$cmd->confirm("⚠️ Bạn có chắc chắn muốn XÓA database [$name]?")) {
            return Command::SUCCESS;
        }

        DB::statement("DROP DATABASE `$name`");
        $cmd->info("🗑 Database [$name] deleted.");

        return Command::SUCCESS;
    }

    public function list(Command $cmd): int
    {
        $databases = DB::select('SHOW DATABASES');

        $cmd->table(
            ['Database'],
            collect($databases)->map(fn ($db) => [$db->Database])->toArray()
        );

        return Command::SUCCESS;
    }

    protected function exists(string $name): bool
    {
        return !empty(DB::select(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$name]
        ));
    }
}
