<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModuleMakeMigrationCommand extends Command
{
    protected $signature = 'module:make-migration {module} {name}';

    protected $description = 'Create migration and register table into module config';

    public function handle()
    {
        $module =  Str::studly($this->argument('module'));
        $name = $this->argument('name');

        $filesystem = new Filesystem();

        // 1. Parse table name
        $table = $this->parseTableName($module, $name);

        // 2. Create migration
        $this->createMigration($module, $name, $table);

        // 3. Update config
        $this->updateModuleConfig($module, $table);

        $this->info("✅ Migration + config updated for table: {$table}");
    }

    protected function databaseExists(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    protected function parseTableName($module, $name)
    {
        // create_posts_table → posts
        preg_match('/create_(.*?)_table/', $name, $matches);

        if (!isset($matches[1])) {
            $this->error('❌ Invalid migration name format');
            exit;
        }

        return Str::snake($module) . '_' . $matches[1];
    }


    protected function createMigration($module, $name, $table)
    {
        // 🔍 1. Check DB tồn tại (tránh crash)
        if (!$this->databaseExists()) {
            $this->warn("⚠️ Database không tồn tại → vẫn tạo migration (skip check table)");
        } else {

            // 🔍 2. Check table tồn tại
            if (Schema::hasTable($table)) {
                $this->warn("⚠️ Table '{$table}' đã tồn tại → không tạo migration");
                return;
            }
        }

        // 📂 3. Tạo folder nếu chưa có
        $path = base_path("Modules/{$module}/database/migrations");

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        // 🕒 4. Generate file
        $timestamp = now()->format('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $fullPath = "{$path}/{$fileName}";

        // 🧱 5. Stub
        $stub = $this->getStub($table);

        file_put_contents($fullPath, $stub);

        $this->info("📦 Migration created: {$fileName}");
    }

    protected function getStub($table)
    {
        return <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;
    }

    protected function updateModuleConfig($module, $table)
    {
        $configPath = base_path("Modules/{$module}/config/module.php");

        if (!file_exists($configPath)) {
            $this->error("❌ module.php not found");
            return;
        }

        $config = include $configPath;

        $tables = $config['tables'] ?? [];

        // tránh duplicate
        if (!in_array($table, $tables)) {
            $tables[] = $table;
        }

        $config['tables'] = $tables;

        $export = var_export($config, true);

        file_put_contents($configPath, "<?php\n\nreturn {$export};\n");

        $this->info("🧠 Config updated: {$table}");
    }
}
