<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class CreateLivewireModuleComponent extends Command
{
    protected $signature = 'create:livewire
        {module : Tên Module (VD: Admin)}
        {component : Component path (VD: post.index)}
        {--delete : Xóa component và view}';

    protected $description = 'Tạo hoặc xóa Livewire component trong Module (hỗ trợ thư mục con).';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $moduleInput    = $this->argument('module');
        $componentInput = $this->argument('component');

        $module = Str::studly($moduleInput);

        $modulePath = base_path("Modules/{$module}");
        if (! $this->files->isDirectory($modulePath)) {
            $this->error("❌ Module {$module} không tồn tại.");
            $this->printGuide();
            return Command::INVALID;
        }

        // ---- Parse component path ----
        $parts = explode('.', $componentInput);
        if (count($parts) < 1) {
            $this->error('❌ Component path không hợp lệ.');
            $this->printGuide();
            return Command::INVALID;
        }

        $componentName = Str::studly(array_pop($parts));
        $foldersStudly = array_map(fn ($p) => Str::studly($p), $parts);
        $foldersKebab  = array_map(fn ($p) => Str::kebab($p), $parts);

        // ---- Paths ----
        $livewireBase = base_path("Modules/{$module}/Livewire");
        $viewBase     = base_path("Modules/{$module}/resources/views/livewire");

        $componentDir = $livewireBase . (count($foldersStudly) ? '/' . implode('/', $foldersStudly) : '');
        $viewDir      = $viewBase . (count($foldersKebab) ? '/' . implode('/', $foldersKebab) : '');

        $componentPath = "{$componentDir}/{$componentName}.php";
        $viewPath      = "{$viewDir}/" . Str::kebab($componentName) . '.blade.php';

        // ---- DELETE ----
        if ($this->option('delete')) {
            $deleted = false;

            if ($this->files->exists($componentPath)) {
                $this->files->delete($componentPath);
                $this->info("🗑️ Đã xóa component: {$componentPath}");
                $deleted = true;
            }

            if ($this->files->exists($viewPath)) {
                $this->files->delete($viewPath);
                $this->info("🗑️ Đã xóa view: {$viewPath}");
                $deleted = true;
            }

            // ✅ Cleanup empty folders
            $this->cleanupEmptyDirectories($componentDir, $livewireBase);
            $this->cleanupEmptyDirectories($viewDir, $viewBase);

            if (! $deleted) {
                $this->warn('⚠️ Không tìm thấy component hoặc view để xóa.');
            }

            return Command::SUCCESS;
        }

        // ---- CREATE DIRECTORIES ----
        foreach ([$componentDir, $viewDir] as $dir) {
            if (! $this->files->isDirectory($dir)) {
                $this->files->makeDirectory($dir, 0755, true);
                $this->info("📁 Đã tạo thư mục: {$dir}");
            }
        }

        // ---- CREATE COMPONENT ----
        if (! $this->files->exists($componentPath)) {
            $viewName = $module . '::livewire'
                . (count($foldersKebab) ? '.' . implode('.', $foldersKebab) : '')
                . '.' . Str::kebab($componentName);

            $namespace = 'Modules\\' . $module . '\\Livewire'
                . (count($foldersStudly) ? '\\' . implode('\\', $foldersStudly) : '');

            $this->files->put($componentPath, <<<PHP
<?php

namespace {$namespace};

use Livewire\Component;

class {$componentName} extends Component
{
    public function render()
    {
        return view('{$viewName}');
    }
}
PHP
            );

            $this->info("✅ Đã tạo component: {$componentPath}");
        }

        // ---- CREATE VIEW ----
        if (! $this->files->exists($viewPath)) {
            $this->files->put($viewPath, <<<BLADE
<div>
    <!-- Livewire component: {$componentName} -->
</div>
BLADE
            );

            $this->info("✅ Đã tạo view: {$viewPath}");
        }

        $this->info('🎉 Livewire component sẵn sàng!');
        return Command::SUCCESS;
    }

    // --------------------------------------------------

    protected function cleanupEmptyDirectories(string $path, string $stopAt): void
    {
        while ($path !== $stopAt && $this->files->isDirectory($path)) {
            if (count($this->files->files($path)) === 0 && count($this->files->directories($path)) === 0) {
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
        $this->line('  php artisan create:livewire Admin post.index');
        $this->line('  php artisan create:livewire Admin post.index --delete');
        $this->line('');
        $this->line('📂 KẾT QUẢ:');
        $this->line('  Modules/Admin/Livewire/Post/Index.php');
        $this->line('  Modules/Admin/resources/views/livewire/post/index.blade.php');
        $this->line('');
    }
}
