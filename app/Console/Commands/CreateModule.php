<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\File;

class CreateModule extends Command
{
    protected $signature = 'create:module
        {name : Ten module, vd: Admin}
        {--type=domain : Loai module: shell|domain|support}
        {--delete : Xoa module}';

    protected $description = 'Tao hoac xoa module theo kieu shell, domain, support';

    protected Filesystem $files;

    protected array $supportedTypes = ['shell', 'domain', 'support'];

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $type = strtolower((string) $this->option('type'));
        $modulePath = base_path("Modules/{$name}");

        if (! in_array($type, $this->supportedTypes, true)) {
            $this->error("Loai module [{$type}] khong hop le. Chi ho tro: shell, domain, support.");
            $this->printGuide();

            return self::INVALID;
        }

        if ($this->option('delete')) {
            return $this->deleteModule($modulePath, $name);
        }

        if ($this->files->exists($modulePath)) {
            $this->error("Module {$name} da ton tai.");

            return self::INVALID;
        }

        foreach ($this->directoriesForType($name, $type) as $directory) {
            $this->files->makeDirectory(base_path("Modules/{$directory}"), 0755, true, true);
        }

        $this->createBaseFiles($name, $type);
        $this->runScaffoldCommands($name, $type);

        $this->newLine();
        $this->info("Da tao module {$name} theo kieu {$type}.");
        $this->line("Thu muc: {$modulePath}");

        return self::SUCCESS;
    }

    protected function deleteModule(string $modulePath, string $name): int
    {
        if (! File::exists($modulePath)) {
            $this->error("Module {$name} không tồn tại.");
            return self::INVALID;
        }

        $module = strtolower($name);

        // 🧠 1. Xóa permissions
        Permission::where('name', 'like', "%_{$module}")->get()
            ->each(function ($permission) {
                $permission->roles()->detach(); // an toàn hơn
                $permission->delete();
            });

        $this->info("🗑️ Đã xóa permissions của module: {$module}");

        // 📂 2. Xóa folder (nếu muốn)
        File::deleteDirectory($modulePath);

        $this->info("✅ Đã xóa module {$name}");

        return self::SUCCESS;
    }

    protected function directoriesForType(string $name, string $type): array
    {
        $common = [
            $name,
            "{$name}/config",
            "{$name}/routes",
            "{$name}/resources",
            "{$name}/resources/lang",
            "{$name}/resources/views",
            "{$name}/resources/views/components",
            "{$name}/resources/views/layouts",
            "{$name}/resources/views/pages",
            "{$name}/resources/views/partials",
            "{$name}/Http",
            "{$name}/Http/Controllers",
            "{$name}/Http/Controllers/Api",
        ];

        return match ($type) {
            'shell' => array_merge($common, [
                "{$name}/Livewire",
                "{$name}/Livewire/Partials",
                "{$name}/Livewire/Dashboard",
                "{$name}/Services",
                "{$name}/resources/views/livewire",
                "{$name}/resources/views/livewire/partials",
                "{$name}/resources/views/livewire/dashboard",
            ]),
            'support' => array_merge($common, [
                "{$name}/Actions",
                "{$name}/Services",
                "{$name}/Models",
                "{$name}/Policies",
                "{$name}/Http/Middleware",
                "{$name}/database",
                "{$name}/database/migrations",
                "{$name}/database/seeders",
                "{$name}/Events",
                "{$name}/Jobs",
                "{$name}/resources/views/livewire",
            ]),
            default => array_merge($common, [
                "{$name}/Actions",
                "{$name}/Services",
                "{$name}/Models",
                "{$name}/Policies",
                "{$name}/Enums",
                "{$name}/DTOs",
                "{$name}/Observers",
                "{$name}/Livewire",
                "{$name}/database",
                "{$name}/database/factories",
                "{$name}/database/migrations",
                "{$name}/database/seeders",
                "{$name}/Events",
                "{$name}/Jobs",
                "{$name}/resources/views/livewire",
            ]),
        };
    }

    protected function createBaseFiles(string $name, string $type): void
    {
        $viewName = Str::kebab($name);

        $this->putIfMissing(
            base_path("Modules/{$name}/config/module.php"),
            $this->moduleConfigTemplate($name, $type)
        );

        $this->putIfMissing(
            base_path("Modules/{$name}/resources/views/{$viewName}.blade.php"),
            $this->moduleEntryViewTemplate($name, $type)
        );

        $this->putIfMissing(
            base_path("Modules/{$name}/resources/views/components/placeholder.blade.php"),
            $this->componentTemplate($name, $type)
        );

        $this->putIfMissing(
            base_path("Modules/{$name}/resources/views/pages/index.blade.php"),
            $this->pageTemplate($name, $type)
        );

        if (in_array($type, ['shell', 'domain'], true)) {
            $this->putIfMissing(
                base_path("Modules/{$name}/resources/views/livewire/placeholder.blade.php"),
                $this->livewireViewTemplate($name, $type)
            );
        }
    }

    protected function runScaffoldCommands(string $name, string $type): void
    {
        $module = Str::lower($name);

        if (in_array($type, ['domain', 'support'], true)) {
            Artisan::call('create:model', [
                'module' => $name,
                'model' => $name,
            ]);
            $this->output->write(Artisan::output());
        }

        Artisan::call('create:controller', [
            'module' => $name,
            'controller' => $name,
        ]);
        $this->output->write(Artisan::output());

        Artisan::call('create:routes', [
            'name' => $module,
        ]);
        $this->output->write(Artisan::output());

        if (in_array($type, ['shell', 'domain'], true)) {
            Artisan::call('permission:module', [
                'name' => $module,
            ]);
            $this->output->write(Artisan::output());
        }
    }

    protected function moduleConfigTemplate(string $name, string $type): string
    {
        return <<<PHP
<?php

return [
    'name' => '{$name}',
    'type' => '{$type}',
    'enabled' => true,
];
PHP;
    }

    protected function moduleEntryViewTemplate(string $name, string $type): string
    {
        return <<<BLADE
@extends('Admin::layouts.master')

@section('title', '{$name}')

@section('content')
    <div class="space-y-4">
        <h1 class="text-2xl font-semibold text-slate-900">{$name} module</h1>
        <p class="text-sm text-slate-500">Loai module: {$type}.</p>

        @include('{$name}::components.placeholder')
    </div>
@endsection
BLADE;
    }

    protected function pageTemplate(string $name, string $type): string
    {
        return <<<BLADE
<div class="space-y-3">
    <h2 class="text-lg font-semibold text-slate-900">{$name} page placeholder</h2>
    <p class="text-sm text-slate-500">Dung de tach page wrappers neu module {$name} can nhieu man hinh.</p>
    @include('{$name}::components.placeholder')
</div>
BLADE;
    }

    protected function componentTemplate(string $name, string $type): string
    {
        return <<<BLADE
<div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm">
    Module <span class="font-semibold text-slate-900">{$name}</span> duoc scaffold theo kieu <span class="font-semibold text-slate-900">{$type}</span>.
</div>
BLADE;
    }

    protected function livewireViewTemplate(string $name, string $type): string
    {
        return <<<BLADE
<div>
    <!-- {$name} {$type} livewire placeholder -->
</div>
BLADE;
    }

    protected function putIfMissing(string $path, string $contents): void
    {
        if (! $this->files->exists($path)) {
            $this->files->put($path, $contents);
        }
    }

    protected function printGuide(): void
    {
        $this->line('');
        $this->line('CACH DUNG:');
        $this->line('  php artisan create:module Admin --type=shell');
        $this->line('  php artisan create:module Pharma --type=domain');
        $this->line('  php artisan create:module Role --type=support');
        $this->line('  php artisan create:module Pharma --delete');
        $this->line('');
    }
}
