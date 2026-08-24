<?php

namespace Tests\Feature\Request\Architecture;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RequestArchitectureTest extends TestCase
{
    public function test_manifest_matches_the_approved_shell_only_contract(): void
    {
        $manifest = require base_path('Modules/Request/config/module.php');

        $this->assertSame('Request', $manifest['name']);
        $this->assertSame('domain', $manifest['type']);
        $this->assertFalse($manifest['enabled']);
        $this->assertFalse($manifest['default_enabled']);
        $this->assertSame(['Admin', 'Auth', 'User', 'Role', 'Shared'], $manifest['depends']);

        foreach ($manifest['depends'] as $dependency) {
            $dependencyManifest = require base_path("Modules/{$dependency}/config/module.php");
            $this->assertSame('shell', $dependencyManifest['type'], "Dependency {$dependency} must remain a Shell module.");
        }
    }

    public function test_request_source_has_no_domain_module_or_forbidden_infrastructure_dependency(): void
    {
        $forbiddenNamespaces = collect(File::directories(base_path('Modules')))
            ->map(fn (string $path): array => [basename($path), $this->manifest($path)])
            ->filter(fn (array $module): bool => $module[0] !== 'Request' && ($module[1]['type'] ?? 'domain') === 'domain')
            ->map(fn (array $module): string => 'Modules\\'.$module[0].'\\')
            ->push(
                'App\\Models\\User',
                'Spatie\\Permission\\Models\\',
            )
            ->all();

        foreach (File::allFiles(base_path('Modules/Request')) as $file) {
            $contents = File::get($file->getPathname());

            foreach ($forbiddenNamespaces as $namespace) {
                $this->assertStringNotContainsString($namespace, $contents, "Forbidden dependency in {$file->getRelativePathname()}.");
            }

            preg_match_all('/^use (Modules\\\\[^;]+);$/m', $contents, $moduleImports);
            foreach ($moduleImports[1] as $import) {
                $this->assertTrue(
                    $this->isApprovedShellImport($import),
                    "Unapproved Shell import [{$import}] in {$file->getRelativePathname()}.",
                );
            }

            $this->assertDoesNotMatchRegularExpression('/module\.json|nwidart|serviceWorker\.register|navigator\.serviceWorker/i', $contents);
        }

        $this->assertFileDoesNotExist(base_path('Modules/Request/module.json'));
        $this->assertDirectoryDoesNotExist(base_path('Modules/Request/database/migrations'));
        $this->assertDirectoryDoesNotExist(base_path('Modules/Request/Livewire'));
    }

    public function test_module_provider_does_not_duplicate_root_resource_registration(): void
    {
        $provider = File::get(base_path('Modules/Request/Providers/RequestServiceProvider.php'));

        foreach (['loadRoutesFrom', 'mergeConfigFrom', 'loadViewsFrom', 'loadTranslationsFrom', 'loadMigrationsFrom', 'Livewire::component', 'commands('] as $registration) {
            $this->assertStringNotContainsString($registration, $provider);
        }
    }

    private function manifest(string $modulePath): array
    {
        foreach (['config/module.php', 'Config/module.php'] as $relativePath) {
            if (File::exists($modulePath.'/'.$relativePath)) {
                $manifest = require $modulePath.'/'.$relativePath;

                return is_array($manifest) ? $manifest : [];
            }
        }

        return [];
    }

    private function isApprovedShellImport(string $import): bool
    {
        return str_starts_with($import, 'Modules\\User\\Contracts\\')
            || str_starts_with($import, 'Modules\\User\\Data\\')
            || str_starts_with($import, 'Modules\\Role\\Contracts\\')
            || str_starts_with($import, 'Modules\\Role\\Data\\')
            || str_starts_with($import, 'Modules\\Admin\\')
            || str_starts_with($import, 'Modules\\Auth\\')
            || str_starts_with($import, 'Modules\\Shared\\Contracts\\')
            || str_starts_with($import, 'Modules\\Shared\\Data\\');
    }
}
