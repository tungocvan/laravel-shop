<?php

namespace Tests\Unit\Modules;

use App\Modules\ModuleCatalog;
use App\Modules\ModuleGraphValidator;
use App\Modules\ModuleRegistry;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ModuleRegistryQueueMetadataTest extends TestCase
{
    public function test_publish_keeps_resolved_enabled_state_and_queue_metadata(): void
    {
        $catalog = $this->createMock(ModuleCatalog::class);
        $validator = $this->createMock(ModuleGraphValidator::class);
        $registry = new ModuleRegistry($catalog, $validator);

        $published = $registry->publish(new Collection([
            [
                'name' => 'Pharma',
                'type' => 'domain',
                'enabled' => true,
                'required' => false,
                'depends' => ['Shared'],
                'path' => '/modules/Pharma',
                'source' => 'runtime',
                'manifest' => [
                    'queues' => [
                        ['name' => 'pharma', 'timeout' => 600, 'tries' => 3],
                    ],
                ],
            ],
        ]));

        $this->assertTrue($published['Pharma']['enabled']);
        $this->assertSame('runtime', $published['Pharma']['source']);
        $this->assertSame('pharma', $published['Pharma']['queues'][0]['name']);
        $this->assertSame(600, $published['Pharma']['queues'][0]['timeout']);
        $this->assertSame(3, $published['Pharma']['queues'][0]['tries']);
    }
}
