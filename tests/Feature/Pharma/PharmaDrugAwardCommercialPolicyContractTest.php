<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaDrugAwardCommercialPolicyContractTest extends TestCase
{
    public function test_commercial_policy_is_a_separate_tbmt_workspace_with_dedicated_permissions(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/DrugBidAwardController.php'));
        $page = file_get_contents(base_path('Modules/Pharma/resources/views/pages/drug-bid-award/commercial-policy.blade.php'));
        $permissions = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_21_211000_add_drug_award_commercial_policy_permissions.php'));

        $this->assertStringContainsString("name('commercial-policy')", $routes);
        $this->assertStringContainsString('can:view_pharma_commercial_policies', $routes);
        $this->assertStringContainsString('commercialPolicy(int $id)', $controller);
        $this->assertStringContainsString('commercial-policy-workspace', $page);
        $this->assertStringContainsString('view_pharma_commercial_policies', $permissions);
        $this->assertStringContainsString('manage_pharma_commercial_policies', $permissions);
    }

    public function test_policy_and_assignments_preserve_award_user_and_history_identity(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_21_210000_create_drug_bid_award_commercial_policy_tables.php'));
        $assignment = file_get_contents(base_path('Modules/Pharma/Models/DrugBidAwardCommercialAssignment.php'));

        $this->assertStringContainsString("'result_key'", $migration);
        $this->assertStringContainsString("'drug_bid_award_id'", $migration);
        $this->assertStringContainsString("'user_id'", $migration);
        $this->assertStringContainsString("'share_percentage'", $migration);
        $this->assertStringContainsString("'ended_at'", $migration);
        $this->assertStringContainsString("on('users')->restrictOnDelete()", $migration);
        $this->assertStringContainsString('App\\Models\\User', $assignment);
        $this->assertStringNotContainsString('medicine_id', $migration);
    }

    public function test_result_group_key_is_canonical_and_safe_when_tbmt_is_missing(): void
    {
        $groups = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardResultGroupService.php'));
        $scope = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardDistributionScopeService.php'));

        $this->assertStringContainsString("'tbmt:'.\$code", $groups);
        $this->assertStringContainsString("'award:'.\$award->id", $groups);
        $this->assertStringContainsString('DrugBidAwardResultGroupService $groups', $scope);
        $this->assertStringContainsString('return $this->groups->resultKey($award);', $scope);
    }

    public function test_activation_requires_allocations_assignments_and_exact_share_but_not_full_allocation(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));

        $this->assertStringContainsString('chưa có phân bổ hiệu lực', $service);
        $this->assertStringContainsString('tổng phân bổ vượt số lượng trúng thầu', $service);
        $this->assertStringContainsString('chưa có User phụ trách', $service);
        $this->assertStringContainsString('tổng tỷ lệ User phải bằng 100%', $service);
        $this->assertStringNotContainsString('FULLY_ALLOCATED', $service);
        $this->assertStringContainsString('STATUS_ARCHIVED', $service);
        $this->assertStringContainsString('ended_at', $service);
    }

    public function test_workspace_supports_bulk_assignment_and_three_step_review(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/CommercialPolicyWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php'));

        $this->assertStringContainsString('public array $selectedAwardIds', $component);
        $this->assertStringContainsString('assignSelected', $component);
        $this->assertStringContainsString('limit(50)', $component);
        $this->assertStringContainsString('limit(200)', $component);
        $this->assertStringContainsString('Bước 1', $view);
        $this->assertStringContainsString('Bước 2', $view);
        $this->assertStringContainsString('Bước 3', $view);
        $this->assertStringContainsString('Gán đã chọn', $view);
        $this->assertStringContainsString('Kích hoạt chính sách', $view);
    }
}
