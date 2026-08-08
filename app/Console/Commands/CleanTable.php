<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CleanTable extends Command
{
    /**
     * Cú pháp: php artisan clean:table medicines
     */
    protected $signature = 'clean:table {table : Tên bảng cần xóa trong database và bảng migrations}';

    protected $description = 'Xóa bảng trong database và dòng tương ứng trong bảng migrations (bỏ qua foreign key).';

    public function handle()
    {
        $table = $this->argument('table');

        // Kiểm tra bảng có tồn tại không
        if (!$this->tableExists($table)) {
            $this->warn("⚪ Bảng '{$table}' không tồn tại trong database.");
        } else {
            try {
                // Tắt kiểm tra khóa ngoại
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::statement("DROP TABLE IF EXISTS `$table`");
                DB::statement('SET FOREIGN_KEY_CHECKS=1');

                $this->info("🗑️ Đã xóa bảng '{$table}' thành công (bỏ qua khóa ngoại).");
            } catch (\Exception $e) {
                $this->error("❌ Lỗi khi xóa bảng '{$table}': " . $e->getMessage());
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                return Command::FAILURE;
            }
        }

        // Xóa migration trong database và quét file
        $migrationPaths = [
            database_path('migrations'),       // core migrations
            base_path('Modules')               // quét tất cả modules
        ];

        $migrationNames = [];

        foreach ($migrationPaths as $path) {
            if ($path === base_path('Modules')) {
                // Quét tất cả module
                if (File::exists($path)) {
                    $modules = File::directories($path);
                    foreach ($modules as $moduleDir) {
                        $moduleMigrationPath = $moduleDir . '/database/migrations';
                        if (File::exists($moduleMigrationPath)) {
                            $migrationNames = array_merge($migrationNames, $this->getMigrationsForTable($moduleMigrationPath, $table));
                        }
                    }
                }
            } else {
                // core migration
                if (File::exists($path)) {
                    $migrationNames = array_merge($migrationNames, $this->getMigrationsForTable($path, $table));
                }
            }
        }

        // Xóa các dòng trong DB
        if (!empty($migrationNames)) {
            foreach ($migrationNames as $migrationName) {
                DB::table('migrations')->where('migration', $migrationName)->delete();
                $this->info("🧹 Đã xóa dòng migration: {$migrationName}");
            }
            $this->info("✅ Hoàn tất — bạn có thể chạy lại php artisan migrate.");
        } else {
            $this->warn("⚠️ Không tìm thấy migration nào chứa Schema::create('{$table}').");
        }

        return Command::SUCCESS;
    }

    protected function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    /**
     * Lấy danh sách migration chứa bảng
     */
    protected function getMigrationsForTable(string $path, string $table): array
    {
        $files = File::files($path);
        $migrationNames = [];

        foreach ($files as $file) {
            $content = File::get($file->getRealPath());
            if (preg_match("/Schema::create\(['\"]{$table}['\"]/", $content)) {
                $migrationNames[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            }
        }

        return $migrationNames;
    }
}
