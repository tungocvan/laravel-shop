<?php

namespace Tests\Feature\System;

use App\Modules\ModuleGraphValidator;
use LogicException;
use Tests\TestCase;

class ModuleGraphValidatorTest extends TestCase
{
    public function test_validator_rejects_missing_disabled_self_and_circular_dependencies(): void
    {
        $validator = new ModuleGraphValidator;
        $cases = [
            'missing module' => collect([$this->module('A', depends: ['Missing'])]),
            'disabled module' => collect([
                $this->module('A', depends: ['B']),
                $this->module('B', enabled: false),
            ]),
            'depend on itself' => collect([$this->module('A', depends: ['A'])]),
            'Circular module dependency' => collect([
                $this->module('A', depends: ['B']),
                $this->module('B', depends: ['A']),
            ]),
        ];

        foreach ($cases as $message => $modules) {
            try {
                $validator->validate($modules);
                $this->fail("Expected graph validation failure containing [{$message}].");
            } catch (LogicException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }
    }

    public function test_runtime_state_transition_is_validated_without_mutating_input(): void
    {
        $validator = new ModuleGraphValidator;
        $modules = collect([
            $this->module('Dependency'),
            $this->module('Consumer', depends: ['Dependency']),
        ]);

        try {
            $validator->withState($modules, 'Dependency', false);
            $this->fail('Disabling an active dependency must fail.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('disabled module [Dependency]', $exception->getMessage());
        }

        $this->assertTrue($modules->firstWhere('name', 'Dependency')['enabled']);

        $updated = $validator->withState($modules, 'Consumer', false);
        $this->assertFalse($updated->firstWhere('name', 'Consumer')['enabled']);
        $this->assertSame('runtime', $updated->firstWhere('name', 'Consumer')['source']);
    }

    private function module(string $name, bool $enabled = true, array $depends = []): array
    {
        return [
            'name' => $name,
            'enabled' => $enabled,
            'required' => false,
            'depends' => $depends,
            'source' => 'manifest',
        ];
    }
}
