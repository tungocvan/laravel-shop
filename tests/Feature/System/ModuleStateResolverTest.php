<?php

namespace Tests\Feature\System;

use App\Modules\ModuleStateRepository;
use App\Modules\ModuleStateResolver;
use Mockery;
use Tests\TestCase;

class ModuleStateResolverTest extends TestCase
{
    public function test_runtime_state_overrides_default_enabled(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Demo')->once()->andReturn(false);

        $resolved = (new ModuleStateResolver($states))->resolve(
            'Demo',
            ['default_enabled' => true, 'enabled' => true],
            'manifest',
            false,
        );

        $this->assertFalse($resolved['enabled']);
        $this->assertSame('runtime', $resolved['source']);
    }

    public function test_default_enabled_precedes_legacy_enabled(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Demo')->once()->andReturnNull();

        $resolved = (new ModuleStateResolver($states))->resolve(
            'Demo',
            ['default_enabled' => false, 'enabled' => true],
            'manifest',
            false,
        );

        $this->assertFalse($resolved['enabled']);
        $this->assertSame('manifest', $resolved['source']);
    }

    public function test_legacy_enabled_remains_backward_compatible(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Demo')->once()->andReturnNull();

        $resolved = (new ModuleStateResolver($states))->resolve('Demo', ['enabled' => false], 'manifest', false);

        $this->assertFalse($resolved['enabled']);
    }

    public function test_missing_runtime_and_manifest_state_defaults_to_enabled(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Demo')->once()->andReturnNull();

        $resolved = (new ModuleStateResolver($states))->resolve('Demo', [], 'fallback', false);

        $this->assertTrue($resolved['enabled']);
    }

    public function test_required_module_cannot_be_disabled_by_runtime_state(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Admin')->once()->andReturn(false);

        $resolved = (new ModuleStateResolver($states))->resolve('Admin', ['enabled' => false], 'manifest', true);

        $this->assertTrue($resolved['enabled']);
        $this->assertSame('runtime-required', $resolved['source']);
    }
}
