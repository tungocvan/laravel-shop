<?php

namespace App\Modules\Migration\Guards;

use Exception;

class PrefixGuard
{
    public function check(string $module, string $filePath): void
    {
        $prefix = strtolower($module) . '_';

        $content = file_get_contents($filePath);

        // match Schema::create('table')
        preg_match_all("/Schema::(create|table)\(['\"](.*?)['\"]/", $content, $matches);

        $tables = $matches[2] ?? [];

        foreach ($tables as $table) {

            // ❌ ignore system tables nếu muốn
            if ($this->ignore($table)) {
                continue;
            }

            if (!str_starts_with($table, $prefix)) {
                throw new Exception(
                    "❌ Table [{$table}] must use prefix [{$prefix}] in file: {$filePath}"
                );
            }
        }
    }

    protected function ignore(string $table): bool
    {
        return in_array($table, [
            'users',
            'password_resets',
            'failed_jobs',
            'personal_access_tokens',
        ]);
    }
}
