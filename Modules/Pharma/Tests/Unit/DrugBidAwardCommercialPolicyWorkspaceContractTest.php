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
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/Livewire/DrugBidAward/CommercialPolicyWorkspace.php');
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php');

        $this->assertStringContainsString('Điều chỉnh phân công theo bệnh viện', $view);
        $this->assertStringContainsString('wire:click="openHospitalAssignment({{ $hospitalGroup[\'partner_id\'] }})"', $view);
        $this->assertStringContainsString("public function openHospitalAssignment(int \$partnerId): void", $component);
        $this->assertStringContainsString("\$this->assignmentMode = 'multiple';", $component);
        $this->assertStringContainsString('wire:model.live="selectedPartnerId"', $view);
        $this->assertStringContainsString('User hiện tại', $view);
        $this->assertStringContainsString('Thay/Gán User cho đã chọn', $view);
    }

    public function test_workspace_supports_single_user_multi_user_and_full_reset_flows(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/Livewire/DrugBidAward/CommercialPolicyWorkspace.php');
        $service = file_get_contents($root.'/Services/DrugBidAwardCommercialPolicyService.php');
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php');

        $this->assertStringContainsString("public string \$assignmentMode = 'single'", $component);
        $this->assertStringContainsString('assignSingleManagerToAll', $component);
        $this->assertStringContainsString('resetAllManagerAssignments', $component);
        $this->assertStringContainsString('removeAllManagers', $service);
        $this->assertStringContainsString('Một User phụ trách toàn bộ', $view);
        $this->assertStringContainsString('Nhiều User phụ trách', $view);
        $this->assertStringContainsString('Phân công toàn bộ', $view);
        $this->assertStringContainsString('Gỡ toàn bộ phân công', $view);
        $this->assertStringContainsString("assignmentMode === 'multiple'", $view);
    }

    public function test_assignment_completeness_requires_resolvable_user_and_partner(): void
    {
        $component = file_get_contents(dirname(__DIR__, 2).'/Livewire/DrugBidAward/CommercialPolicyWorkspace.php');

        $this->assertStringContainsString('$validAssignmentRows=$assignmentRows->filter', $component);
        $this->assertStringContainsString('$row->user !== null && $row->partner !== null', $component);
        $this->assertStringContainsString("'assigned'=>\$validAssignmentRows->count()", $component);
        $this->assertStringContainsString("'users'=>\$validAssignmentRows->pluck('user_id')->unique()->count()", $component);
    }


    public function test_persisted_assignment_mode_locks_the_opposite_flow_until_reset(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/Livewire/DrugBidAward/CommercialPolicyWorkspace.php');
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php');

        $this->assertStringContainsString("public string \$persistedAssignmentMode = 'unassigned'", $component);
        $this->assertStringContainsString('syncAssignmentModeFromDatabase', $component);
        $this->assertStringContainsString("\$rows->pluck('user_id')->unique()->count() === 1", $component);
        $this->assertStringContainsString("\$this->persistedAssignmentMode = \$isSingleComplete ? 'single' : 'multiple'", $component);
        $this->assertStringContainsString("abort_if(\$this->persistedAssignmentMode === 'single'", $component);
        $this->assertStringContainsString("abort_if(\$this->persistedAssignmentMode === 'multiple'", $component);
        $this->assertStringContainsString("@disabled(\$persistedAssignmentMode === 'multiple')", $view);
        $this->assertStringContainsString("@disabled(\$persistedAssignmentMode === 'single')", $view);
        $this->assertStringContainsString('Chế độ hiện tại:', $view);
        $this->assertStringContainsString('Gỡ toàn bộ phân công để đổi cách phân công.', $view);
    }


    public function test_single_mode_keeps_review_and_whole_manager_replacement_available(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/Livewire/DrugBidAward/CommercialPolicyWorkspace.php');
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php');

        $this->assertStringContainsString('prepareSingleManagerReplacement', $component);
        $this->assertStringContainsString('replaceSingleManager', $component);
        $this->assertStringContainsString("\$this->selectedPartnerId = (string) \$partnerId;", $component);
        $this->assertStringContainsString('Thay User toàn bộ', $view);
        $this->assertStringContainsString('Chi tiết bệnh viện', $view);
        $this->assertStringContainsString('chỉ xem trong chế độ Một User phụ trách toàn bộ', $view);
        $this->assertStringContainsString('wire:click="replaceSingleManager"', $view);
    }


    public function test_hospital_policy_override_is_persisted_on_allocation_with_product_fallback(): void
    {
        $root = dirname(__DIR__, 2);
        $model = file_get_contents($root.'/Models/DrugBidAwardAllocation.php');
        $service = file_get_contents($root.'/Services/DrugBidAwardCommercialPolicyService.php');
        $component = file_get_contents($root.'/Livewire/DrugBidAward/CommercialPolicyWorkspace.php');
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php');

        $this->assertStringContainsString('commercial_policy_percentage', $model);
        $this->assertStringContainsString('saveHospitalPolicyOverride', $service);
        $this->assertStringContainsString('public array $hospitalPolicyOverrides = []', $component);
        $this->assertStringContainsString('resetHospitalPolicyOverride', $component);
        $this->assertStringContainsString('Chính sách chuẩn', $view);
        $this->assertStringContainsString('Chính sách BV', $view);
        $this->assertStringContainsString('Chính sách áp dụng', $view);
        $this->assertStringContainsString('wire:change="saveHospitalPolicyOverride', $view);
        $this->assertStringContainsString('Đặt lại', $view);
    }

    public function test_single_user_summary_has_no_duplicate_assignment_actions(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php');

        $this->assertStringNotContainsString('wire:click="removeManagerGroup', $view);
        $this->assertStringNotContainsString("prepareSingleManagerReplacement' : 'selectManagementUser", $view);
    }

}
