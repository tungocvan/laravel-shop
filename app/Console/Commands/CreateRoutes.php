<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateRoutes extends Command
{
    /**
     * Tên và cú pháp của lệnh.
     */
    protected $signature = 'create:routes {name : Tên module}';

    /**
     * Mô tả lệnh.
     */
    protected $description = 'Tạo file routes web.php và api.php cho module';

    /**
     * Thực thi lệnh.
     */
    public function handle(): void
    {
        $name = ucfirst($this->argument('name'));
        $module = strtolower($name);
        $modulePath = base_path("Modules/{$name}/routes");

        // Tạo thư mục routes nếu chưa tồn tại
        if (!File::exists($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
            $this->info("📁 Đã tạo thư mục: {$modulePath}");
        }

        /**
         * Nội dung routes web
         */
        $webContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Modules\\{$name}\\Http\\Controllers\\{$name}Controller;

Route::middleware(['web','auth:admin'])
    ->prefix('/{$module}')
    ->name('{$module}.')
    ->group(function () {
        Route::get('/', [{$name}Controller::class, 'index'])->name('index');
    });
PHP;

        /**
         * Nội dung routes api
         */
        $apiContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Modules\\{$name}\\Http\\Controllers\\Api\\{$name}Controller;

// Route::middleware('auth:sanctum')
//     ->controller({$name}Controller::class)
//     ->prefix('{$module}')
//     ->group(function () {
//         Route::get('/', 'index');
//     });

Route::prefix('{$module}')
    ->controller({$name}Controller::class)
    ->group(function () {
        Route::get('/', 'index');
    });
PHP;

        // Ghi file
        File::put("{$modulePath}/web.php", $webContent);
        File::put("{$modulePath}/api.php", $apiContent);

        $this->info("✅ Đã tạo routes web.php & api.php cho module {$name}");
        $this->newLine();
        $this->info("🎉 Hoàn tất!");
    }
}
