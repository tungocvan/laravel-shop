<?php

namespace App\Services\Mysql;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class BackupService
{
    public function backup(Command $cmd, ?string $path): int
    {
        if (!$path) {
            $cmd->error('File path is required.');
            return Command::FAILURE;
        }

        $process = Process::fromShellCommandline(
            sprintf(
                'MYSQL_PWD=%s mysqldump -h %s -u %s %s > %s',
                escapeshellarg(config('database.connections.mysql.password')),
                escapeshellarg(config('database.connections.mysql.host')),
                escapeshellarg(config('database.connections.mysql.username')),
                escapeshellarg(config('database.connections.mysql.database')),
                escapeshellarg($path)
            )
        );

        $process->run();

        if (!$process->isSuccessful()) {
            $cmd->error('Backup failed.');
            return Command::FAILURE;
        }

        $cmd->info("✅ Backup thành công: $path");
        return Command::SUCCESS;
    }

    public function restore(Command $cmd, ?string $path): int
    {
        if (!$path || !file_exists($path)) {
            $cmd->error('File không tồn tại.');
            return Command::FAILURE;
        }

        if (!$cmd->option('force') &&
            !$cmd->confirm('⚠️ Restore sẽ GHI ĐÈ dữ liệu hiện tại. Tiếp tục?')) {
            return Command::SUCCESS;
        }

        $process = Process::fromShellCommandline(
            sprintf(
                'MYSQL_PWD=%s mysql -h %s -u %s %s < %s',
                escapeshellarg(config('database.connections.mysql.password')),
                escapeshellarg(config('database.connections.mysql.host')),
                escapeshellarg(config('database.connections.mysql.username')),
                escapeshellarg(config('database.connections.mysql.database')),
                escapeshellarg($path)
            )
        );

        $process->run();

        if (!$process->isSuccessful()) {
            $cmd->error('Restore failed.');
            return Command::FAILURE;
        }

        $cmd->info("♻ Restore thành công từ: $path");
        return Command::SUCCESS;
    }

    public function import(Command $cmd, ?string $path): int
    {
        return $this->restore($cmd, $path);
    }
}
