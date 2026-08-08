<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class CreateModel extends Command
{
    protected $signature = 'create:model
        {module : Tên Module (VD: Admin)}
        {model  : Tên Model (VD: Post)}
        {--delete : Xóa model}';

    protected $description = 'Tạo hoặc xóa Model trong Module (chuẩn hóa tên, auto folder).';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        // ---- Normalize input ----
        $module = Str::studly($this->argument('module'));
        $model  = Str::studly($this->argument('model'));

        // ---- Check module exists ----
        $modulePath = base_path("Modules/{$module}");
        if (! $this->files->isDirectory($modulePath)) {
            $this->error("❌ Module {$module} không tồn tại.");
            $this->printGuide();
            return Command::INVALID;
        }

        $modelsPath = "{$modulePath}/Models";
        $modelPath  = "{$modelsPath}/{$model}.php";

        // ==================================================
        // DELETE MODE
        // ==================================================
        if ($this->option('delete')) {

            if (! $this->files->exists($modelPath)) {
                $this->error("❌ Model {$model} không tồn tại để xóa.");
                $this->printGuide();
                return Command::INVALID;
            }

            $this->files->delete($modelPath);
            $this->info("🗑️ Đã xóa model: {$modelPath}");

            // 🧹 Xóa thư mục Models nếu rỗng
            if ($this->files->isDirectory($modelsPath)
                && count($this->files->files($modelsPath)) === 0
                && count($this->files->directories($modelsPath)) === 0
            ) {
                $this->files->deleteDirectory($modelsPath);
                $this->info("🧹 Đã xóa thư mục rỗng: {$modelsPath}");
            }

            return Command::SUCCESS;
        }

        // ==================================================
        // CREATE MODE
        // ==================================================

        // ---- Ensure Models directory ----
        if (! $this->files->isDirectory($modelsPath)) {
            $this->files->makeDirectory($modelsPath, 0755, true);
            $this->info("📁 Đã tạo thư mục: {$modelsPath}");
        }

        if ($this->files->exists($modelPath)) {
            $this->error("❌ Model {$model} đã tồn tại.");
            $this->printGuide();
            return Command::INVALID;
        }

        // ---- Model template ----
        $template = <<<PHP
<?php

namespace Modules\\{$module}\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;

class {$model} extends Model
{
    use HasFactory;

    // protected \$connection = 'mysql';
    // protected \$table = '';
    // protected \$primaryKey = 'id';
    // protected \$fillable = [];
    // protected \$hidden = [];
    // public \$timestamps = true;
}
PHP;

        $this->files->put($modelPath, $template);

        $this->info("✅ Model {$model} đã được tạo thành công.");
        $this->line("📂 {$modelPath}");

        return Command::SUCCESS;
    }

    // --------------------------------------------------

    protected function printGuide(): void
    {
        $this->line('');
        $this->line('📘 CÁCH DÙNG:');
        $this->line('  php artisan create:model Admin Post');
        $this->line('  php artisan create:model Admin Post --delete');
        $this->line('');
        $this->line('📂 KẾT QUẢ:');
        $this->line('  Modules/Admin/Models/Post.php');
        $this->line('');
    }
}
