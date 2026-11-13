<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaPriceListEffectiveFilterContractTest extends TestCase
{
    #[Test]
    public function default_browse_uses_active_status_without_implicit_date_window(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workspace = file_get_contents(base_path('Modules/Pharma/Services/UserPriceListWorkspace.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-lists.blade.php'));

        $this->assertStringContainsString("\$status = \$validated['status'] ?? PriceList::STATUS_ACTIVE;", $controller);
        $this->assertStringContainsString("\$fromDate = \$validated['from_date'] ?? null;", $controller);
        $this->assertStringContainsString("\$toDate = \$validated['to_date'] ?? null;", $controller);
        $this->assertStringContainsString("->activeAt(now())", $workspace);
        $this->assertStringContainsString("orWhereDate('effective_to', '>=', \$fromDate)", $workspace);
        $this->assertStringContainsString("orWhereDate('effective_from', '<=', \$toDate)", $workspace);
        $this->assertStringContainsString("'all' => ['label' => 'Tất cả'", $view);
        $this->assertStringNotContainsString('now()->startOfMonth()->toDateString()', $view);
    }
}
