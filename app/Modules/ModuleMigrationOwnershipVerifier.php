<?php

namespace App\Modules;

use Illuminate\Support\Facades\Schema;

class ModuleMigrationOwnershipVerifier
{
    public function verify(array $module): array
    {
        $ownership = $this->ownership($module);

        return collect($ownership)->mapWithKeys(function (array $requirements, string $migration): array {
            $missingTables = [];
            $missingColumns = [];

            foreach ($requirements['tables'] ?? [] as $table) {
                if (! Schema::hasTable((string) $table)) {
                    $missingTables[] = (string) $table;
                }
            }

            foreach ($requirements['columns'] ?? [] as $table => $columns) {
                if (! Schema::hasTable((string) $table)) {
                    $missingTables[] = (string) $table;

                    continue;
                }

                foreach ($columns as $column) {
                    if (! Schema::hasColumn((string) $table, (string) $column)) {
                        $missingColumns[] = (string) $table.'.'.(string) $column;
                    }
                }
            }

            $missingTables = array_values(array_unique($missingTables));

            return [$migration => [
                'verified' => $missingTables === [] && $missingColumns === [],
                'missing_tables' => $missingTables,
                'missing_columns' => $missingColumns,
            ]];
        })->all();
    }

    private function ownership(array $module): array
    {
        foreach (['config/migration_ownership.php', 'Config/migration_ownership.php'] as $relative) {
            $path = $module['path'].DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (is_file($path)) {
                $ownership = require $path;

                return is_array($ownership) ? $ownership : [];
            }
        }

        return [];
    }
}
