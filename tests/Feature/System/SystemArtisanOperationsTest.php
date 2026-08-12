<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Modules\System\Services\SystemOperationService;
use Tests\TestCase;

class SystemArtisanOperationsTest extends TestCase
{
    public function test_operation_registry_exposes_only_approved_operations(): void
    {
        $operations = app(SystemOperationService::class)->operations();

        $this->assertSame(
            ['artisan.list', 'cache.optimize-clear'],
            array_column($operations, 'id'),
        );
    }

    public function test_artisan_list_maps_to_fixed_command(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('list', [])
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Laravel Framework commands');

        $result = app(SystemOperationService::class)->execute('artisan.list', 123);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame('Laravel Framework commands', $result['output']);
    }

    public function test_optimize_clear_maps_to_fixed_command(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear', [])
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Caches cleared');

        $result = app(SystemOperationService::class)->execute('cache.optimize-clear', 123);

        $this->assertSame(0, $result['exit_code']);
        $this->assertSame('Caches cleared', $result['output']);
    }

    public function test_unknown_operation_is_rejected_before_artisan_execution(): void
    {
        Artisan::shouldReceive('call')->never();

        $this->expectException(InvalidArgumentException::class);

        app(SystemOperationService::class)->execute('migrate.fresh', 123);
    }

    public function test_livewire_component_enforces_command_permission_and_has_no_free_form_execution(): void
    {
        $contents = file_get_contents(base_path('Modules/System/Livewire/Settings/ArtisanList.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $contents);
        $this->assertStringContainsString("authorizePermission('system.commands.run')", $contents);
        $this->assertStringContainsString('SystemOperationService', $contents);
        $this->assertStringNotContainsString('artisanCommand', $contents);
        $this->assertStringNotContainsString('Artisan::call', $contents);
    }

    public function test_artisan_ui_does_not_expose_destructive_or_free_form_commands(): void
    {
        $contents = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/artisan-list.blade.php'));

        $this->assertStringNotContainsString('migrate:fresh', $contents);
        $this->assertStringNotContainsString('db:seed', $contents);
        $this->assertStringNotContainsString('key:generate', $contents);
        $this->assertStringNotContainsString('artisanCommand', $contents);
        $this->assertStringContainsString('executeOperation', $contents);
    }

    public function test_production_containment_still_forces_artisan_component_disabled(): void
    {
        $contents = file_get_contents(base_path('Modules/System/Services/SystemConfigService.php'));
        $tabs = require base_path('Modules/System/config/system_tabs.php');
        $artisanTab = collect($tabs)->firstWhere('component', 'system.settings.artisan-list');

        $this->assertStringContainsString("'system.settings.artisan-list'", $contents);
        $this->assertNotNull($artisanTab);
        $this->assertFalse((bool) ($artisanTab['enabled'] ?? true));
    }
}
