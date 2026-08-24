<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestPwaSafetyTest extends TestCase
{
    public function test_request_reuses_global_pwa_shell_without_mutation_queue(): void
    {
        $moduleRoot = base_path('Modules/Request');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($moduleRoot));
        $paths = [];
        $source = '';

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            $paths[] = $relative;

            if (in_array($file->getExtension(), ['php', 'js', 'mjs', 'json'], true)) {
                $source .= "\n".file_get_contents($file->getPathname());
            }
        }

        $this->assertFalse(collect($paths)->contains(fn (string $path): bool => preg_match('/Modules\/Request\/.*(?:service[-_.]?worker|manifest)\.(?:js|json|webmanifest)$/i', $path) === 1));
        $this->assertStringNotContainsString('navigator.serviceWorker.register', $source);
        $this->assertStringNotContainsString('registration.sync.register', $source);
        $this->assertStringNotContainsString('SyncManager', $source);
        $this->assertStringContainsString("return false", file_get_contents(base_path('Modules/Request/resources/js/request-offline-policy.js')));
    }

    public function test_request_offline_runtime_is_user_scoped_and_loaded_from_request_pages_only(): void
    {
        $runtime = file_get_contents(base_path('Modules/Request/resources/js/request-offline.js'));
        $partial = file_get_contents(base_path('Modules/Request/resources/views/partials/offline-runtime.blade.php'));
        $vite = file_get_contents(base_path('vite.config.js'));

        $this->assertStringContainsString("const DB_NAME = 'request-v1'", $runtime);
        $this->assertStringContainsString("[data-request-offline-root]", $runtime);
        $this->assertStringContainsString('data-request-user-id', $partial);
        $this->assertStringContainsString("@vite('Modules/Request/resources/js/request-offline.js')", $partial);
        $this->assertStringContainsString("Modules/Request/resources/js/request-offline.js", $vite);
    }
}
