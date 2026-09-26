<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DrugBidAwardCommercialPolicyWorkspaceContractTest extends TestCase
{
    public function test_management_overview_is_hospital_first_and_does_not_render_flat_assignment_matrix(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/Livewire/DrugBidAward/CommercialPolicyWorkspace.php');
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php');

        $this->assertStringContainsString('$hospitalGroups=$products', $component);
        $this->assertStringContainsString("->filter(fn(\$allocation) => \$allocation->partner !== null)", $component);
        $this->assertStringContainsString("->groupBy('partner_id')", $component);
        $this->assertStringContainsString("'hospitalGroups'", $component);
        $this->assertStringNotContainsString('$assignmentMatrix=', $component);

        $this->assertStringContainsString('Phạm vi quản lý theo bệnh viện', $view);
        $this->assertStringContainsString('Một bệnh viện một dòng', $view);
        $this->assertStringContainsString('{{ $hospitalGroups->count() }} bệnh viện', $view);
        $this->assertStringContainsString('Xem / Điều chỉnh', $view);
        $this->assertStringNotContainsString('$assignmentMatrix', $view);
        $this->assertStringNotContainsString("hospital']?->name ?: '—'", $view);
    }

    public function test_hospital_exception_editor_remains_the_single_product_level_adjustment_surface(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php');

        $this->assertStringContainsString('Điều chỉnh phân công theo bệnh viện', $view);
        $this->assertStringContainsString('wire:click="selectAssignmentContext({{ $hospitalGroup[\'partner_id\'] }})"', $view);
        $this->assertStringContainsString('wire:model.live="selectedPartnerId"', $view);
        $this->assertStringContainsString('User hiện tại', $view);
        $this->assertStringContainsString('Thay/Gán User cho đã chọn', $view);
    }
}
