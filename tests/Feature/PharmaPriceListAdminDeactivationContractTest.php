<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class PharmaPriceListAdminDeactivationContractTest extends TestCase
{
    public function test_admin_deactivation_uses_authorized_audited_workflow_and_reason(): void
    {
        $component = file_get_contents(__DIR__.'/../../Modules/Pharma/Livewire/PriceList/Index.php');
        $view = file_get_contents(__DIR__.'/../../Modules/Pharma/resources/views/livewire/price-list/index.blade.php');
        $workflow = file_get_contents(__DIR__.'/../../Modules/Pharma/Services/PriceListDeactivationWorkflow.php');

        self::assertStringContainsString('PriceListDeactivationWorkflow $deactivation', $component);
        self::assertStringContainsString('$this->authorizePharmaEdit();', $component);
        self::assertStringContainsString('$deactivation->deactivateDirectly(', $component);
        self::assertStringContainsString('$this->deactivationReason', $component);
        self::assertStringContainsString('wire:model="deactivationReason"', $view);
        self::assertStringContainsString("confirm({{ \$list->id }},'deactivate')", $view);
        self::assertStringContainsString("confirm({{ \$list->id }},'delete')", $view);
        self::assertStringContainsString('PriceList::STATUS_ACTIVE', $workflow);
        self::assertStringContainsString('PriceList::STATUS_INACTIVE', $workflow);
        self::assertStringContainsString("'deactivated_by' => \$approverUserId", $workflow);
    }
}
