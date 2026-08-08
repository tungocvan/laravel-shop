<?php

namespace App\Services\Mysql;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueryService
{
    protected array $allowed = ['select', 'show', 'describe', 'explain'];

    public function run(Command $cmd, ?string $query): int
    {
        if (!$query) {
            $cmd->error('Query is required.');
            return Command::FAILURE;
        }

        $keyword = strtolower(strtok(trim($query), ' '));

        if (!in_array($keyword, $this->allowed)) {
            $cmd->error("❌ Query [$keyword] không được phép.");
            return Command::FAILURE;
        }

        $results = DB::select($query);

        if (empty($results)) {
            $cmd->info('Query executed. No results.');
            return Command::SUCCESS;
        }

        $cmd->table(
            array_keys((array) $results[0]),
            collect($results)->map(fn ($r) => (array) $r)->toArray()
        );

        return Command::SUCCESS;
    }
}
