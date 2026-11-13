<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PharmaKqlcntAssignedUsersContractTest extends TestCase
{
    #[Test]
    public function filter_is_validated_and_applied_before_pagination(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $this->assertStringContainsString("'assigned' => ['nullable', 'in:0,1']", $controller);
        $this->assertStringContainsString("array_keys(\$assignedUsers) ?: [-1]", $controller);
        $this->assertLessThan(strpos($controller, '$items = $awards->paginate($filters);'), strpos($controller, '$filters[\'assigned_group_ids\']'));
        $this->assertStringContainsString("'assignedUsers' => \$assignedUsers", $controller);
    }

    #[Test]
    public function assignment_lookup_requires_active_users_and_verified_group_mapping(): void
    {
        $service = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntAssignedUserOverview.php'));
        $this->assertStringContainsString('bridge->scopeKey($id)', $service);
        $this->assertStringContainsString('bridge->verifiedMappingCandidateIds($id)', $service);
        $this->assertStringContainsString('workflow->managementAssignmentSummary($award)', $service);
        $this->assertStringContainsString('workflow->contextAward($scope)', $service);
        $this->assertStringNotContainsString("where('p.source_type', 'muasamcong')", $service);
    }

    #[Test]
    public function list_shows_users_and_filter_autosubmits(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-awards.blade.php'));
        $this->assertStringContainsString('name="assigned"', $blade);
        $this->assertStringContainsString('Đã có User phụ trách', $blade);
        $this->assertStringContainsString('Chưa có User phụ trách', $blade);
        $this->assertStringContainsString('$assignedUsers[(int) $item->id]', $blade);
        $this->assertStringContainsString('select[name="assigned"]', $blade);
    }
}
