<?php

namespace Tests\Feature\System;

use App\Modules\FileModuleStateRepository;
use RuntimeException;
use Tests\TestCase;

class ModuleStateRepositoryTest extends TestCase
{
    private string $directory;
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/module-state-'.bin2hex(random_bytes(6)));
        $this->path = $this->directory.'/module-state.json';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            foreach (glob($this->directory.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->directory);
        }
        parent::tearDown();
    }

    public function test_missing_file_returns_no_runtime_override_without_creating_file(): void
    {
        $repository = new FileModuleStateRepository($this->path);
        $this->assertSame([], $repository->all());
        $this->assertFalse($repository->has('Website'));
        $this->assertNull($repository->get('Website'));
        $this->assertFileDoesNotExist($this->path);
    }

    public function test_set_lazily_creates_state_and_preserves_other_modules(): void
    {
        $repository = new FileModuleStateRepository($this->path);
        $repository->set('Website', false);
        $repository->set('Admission', true);
        $this->assertFileExists($this->path);
        $this->assertSame(['Website' => false, 'Admission' => true], $repository->all());
    }

    public function test_forget_removes_only_requested_module(): void
    {
        $repository = new FileModuleStateRepository($this->path);
        $repository->set('Website', false);
        $repository->set('Admission', true);
        $repository->forget('Website');
        $this->assertNull($repository->get('Website'));
        $this->assertTrue($repository->get('Admission'));
    }

    public function test_invalid_json_fails_safely(): void
    {
        mkdir($this->directory, 0775, true);
        file_put_contents($this->path, '{invalid');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid JSON');
        (new FileModuleStateRepository($this->path))->all();
    }

    public function test_invalid_module_value_fails_safely(): void
    {
        mkdir($this->directory, 0775, true);
        file_put_contents($this->path, json_encode(['version' => 1, 'modules' => ['Website' => 'false']], JSON_THROW_ON_ERROR));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid module value');
        (new FileModuleStateRepository($this->path))->all();
    }

    public function test_written_payload_has_versioned_schema(): void
    {
        $repository = new FileModuleStateRepository($this->path);
        $repository->set('Website', false);
        $payload = json_decode((string) file_get_contents($this->path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $payload['version']);
        $this->assertSame(['Website' => false], $payload['modules']);
    }

    public function test_state_and_lock_files_are_group_writable_after_mutation(): void
    {
        $repository = new FileModuleStateRepository($this->path);
        $repository->set('Request', true);

        clearstatcache(true, $this->path);
        clearstatcache(true, $this->path.'.lock');

        $this->assertSame('0660', substr(sprintf('%o', fileperms($this->path)), -4));
        $this->assertSame('0660', substr(sprintf('%o', fileperms($this->path.'.lock')), -4));
    }
}
