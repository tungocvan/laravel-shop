<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class CreateController extends Command
{
    protected $signature = 'create:controller
        {module : Tên Module (VD: Admin)}
        {controller : Tên Controller (không có Controller)}
        {--delete : Xóa controller và view}';

    protected $description = 'Tạo hoặc xóa Controller (Web + API) và view cho Module.';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        // ---- Normalize input ----
        $module     = Str::studly($this->argument('module'));
        $name       = Str::studly($this->argument('controller'));
        $viewName   = Str::kebab($name);

        // ---- Base paths ----
        $modulePath = base_path("Modules/{$module}");
        if (! $this->files->isDirectory($modulePath)) {
            $this->error("❌ Module {$module} không tồn tại.");
            $this->printGuide();
            return Command::INVALID;
        }

        $webDir  = "{$modulePath}/Http/Controllers";
        $apiDir  = "{$webDir}/Api";
        $viewDir = "{$modulePath}/resources/views";

        $webControllerPath = "{$webDir}/{$name}Controller.php";
        $apiControllerPath = "{$apiDir}/{$name}Controller.php";
        $viewPath          = "{$viewDir}/{$viewName}.blade.php";

        // ==================================================
        // DELETE MODE
        // ==================================================
        if ($this->option('delete')) {
            $deleted = false;

            foreach ([$webControllerPath, $apiControllerPath, $viewPath] as $file) {
                if ($this->files->exists($file)) {
                    $this->files->delete($file);
                    $this->info("🗑️ Đã xóa: {$file}");
                    $deleted = true;
                }
            }

            if (! $deleted) {
                $this->error("❌ Không tìm thấy controller hoặc view để xóa.");
                $this->printGuide();
                return Command::INVALID;
            }

            // 🧹 Cleanup empty folders
            $this->cleanupEmptyDirectories($apiDir, $webDir);
            $this->cleanupEmptyDirectories($webDir, "{$modulePath}/Http");
            $this->cleanupEmptyDirectories($viewDir, "{$modulePath}/resources");

            return Command::SUCCESS;
        }

        // ==================================================
        // CREATE MODE
        // ==================================================

        // ---- Ensure directories ----
        foreach ([$webDir, $apiDir, $viewDir] as $dir) {
            if (! $this->files->isDirectory($dir)) {
                $this->files->makeDirectory($dir, 0755, true);
                $this->info("📁 Đã tạo thư mục: {$dir}");
            }
        }

        // ---- Check existing ----
        if ($this->files->exists($webControllerPath) || $this->files->exists($apiControllerPath)) {
            $this->error("❌ Controller {$name} đã tồn tại.");
            $this->printGuide();
            return Command::INVALID;
        }

        // ---- Web Controller ----
        $this->files->put($webControllerPath, $this->webControllerTemplate($module, $name, $viewName));
        $this->info("✅ Đã tạo Web Controller: {$webControllerPath}");

        // ---- API Controller ----
        $this->files->put($apiControllerPath, $this->apiControllerTemplate($module, $name));
        $this->info("✅ Đã tạo API Controller: {$apiControllerPath}");

        // ---- View ----
        if (! $this->files->exists($viewPath)) {
            $this->files->put($viewPath, <<<BLADE
<div>
    <!-- View: {$module}::{$viewName} -->
</div>
BLADE
            );
            $this->info("📄 Đã tạo view: {$viewPath}");
        }

        $this->info('🎉 Hoàn tất tạo controller!');
        return Command::SUCCESS;
    }

    // ==================================================
    // Templates
    // ==================================================

    protected function webControllerTemplate(string $module, string $name, string $view): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;

class {$name}Controller extends Controller
{
    public function __construct()
    {
       // \$this->middleware('permission:{$view}-list|{$view}-create|{$view}-edit|{$view}-delete', ['only' => ['index','show']]);
       // \$this->middleware('permission:{$view}-create', ['only' => ['create','store']]);
       // \$this->middleware('permission:{$view}-edit', ['only' => ['edit','update']]);
       // \$this->middleware('permission:{$view}-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        return view('{$module}::{$view}');
    }
}
PHP;
    }

    protected function apiControllerTemplate(string $module, string $name): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use Illuminate\\Http\\Request;

class {$name}Controller extends Controller
{
    //
}
PHP;
    }

    // ==================================================
    // Helpers
    // ==================================================

    protected function cleanupEmptyDirectories(string $path, string $stopAt): void
    {
        while ($path !== $stopAt && $this->files->isDirectory($path)) {
            if (
                count($this->files->files($path)) === 0 &&
                count($this->files->directories($path)) === 0
            ) {
                $this->files->deleteDirectory($path);
                $this->info("🧹 Đã xóa thư mục rỗng: {$path}");
                $path = dirname($path);
            } else {
                break;
            }
        }
    }

    protected function printGuide(): void
    {
        $this->line('');
        $this->line('📘 CÁCH DÙNG:');
        $this->line('  php artisan create:controller Admin Post');
        $this->line('  php artisan create:controller Admin Post --delete');
        $this->line('');
        $this->line('📂 KẾT QUẢ:');
        $this->line('  Modules/Admin/Http/Controllers/PostController.php');
        $this->line('  Modules/Admin/Http/Controllers/Api/PostController.php');
        $this->line('  Modules/Admin/resources/views/post.blade.php');
        $this->line('');
    }
}
