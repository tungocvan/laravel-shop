<?php

namespace Tests\Feature\Request\ModuleState;

use App\Modules\FileModuleStateRepository;
use App\Modules\ModuleStateResolver;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RequestModuleStateTest extends TestCase
{
    private string $statePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statePath = storage_path('framework/testing/request-module-state-'.bin2hex(random_bytes(6)).'.json');
    }

    protected function tearDown(): void
    {
        File::delete([$this->statePath, $this->statePath.'.lock']);
        parent::tearDown();
    }

    public function test_request_is_default_off_and_runtime_toggles_do_not_modify_manifest(): void
    {
        $manifestPath = base_path('Modules/Request/config/module.php');
        $manifestBefore = File::get($manifestPath);
        $manifest = require $manifestPath;
        $states = new FileModuleStateRepository($this->statePath);
        $resolver = new ModuleStateResolver($states);

        $this->assertFalse($resolver->resolve('Request', $manifest, 'manifest', false)['enabled']);

        $states->set('Request', true);
        $this->assertTrue($resolver->resolve('Request', $manifest, 'manifest', false)['enabled']);

        $states->set('Request', false);
        $this->assertFalse($resolver->resolve('Request', $manifest, 'manifest', false)['enabled']);

        $states->forget('Request');
        $this->assertFalse($resolver->resolve('Request', $manifest, 'manifest', false)['enabled']);
        $this->assertSame($manifestBefore, File::get($manifestPath));
    }
}
