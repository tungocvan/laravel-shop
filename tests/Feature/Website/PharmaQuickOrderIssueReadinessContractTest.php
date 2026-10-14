<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\PharmaQuickOrderIssueReadinessService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PharmaQuickOrderIssueReadinessContractTest extends TestCase
{
    #[Test]
    public function handoff_preflight_is_read_only_and_never_creates_or_posts_inventory_issues(): void
    {
        $source = file_get_contents(base_path('Modules/Website/Services/PharmaQuickOrderIssueReadinessService.php'));

        $this->assertStringContainsString("status !== 'accepted'", $source);
        $this->assertStringContainsString("quantity <= 0", $source);
        $this->assertStringContainsString('! $link->medicine_id', $source);
        $this->assertStringContainsString("'ready_for_authoring'", $source);
        $this->assertStringNotContainsString('InventoryIssue::create', $source);
        $this->assertStringNotContainsString('InventoryIssueItem::create', $source);
        $this->assertStringNotContainsString('->post(', $source);
        $this->assertTrue(class_exists(PharmaQuickOrderIssueReadinessService::class));
    }
}
