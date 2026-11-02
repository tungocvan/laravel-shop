<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaDraftShortageValidationRecoveryTest extends TestCase
{
    public function test_item_specific_error_and_old_input_recovery_are_wired(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/UserOrderAuthoringService.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));

        $this->assertStringContainsString("old('draft_shortage_notes.'.\$item->medicine_id, \$current?->draft_shortage_note ?:", $view);
        $this->assertStringContainsString("@error('draft_shortage_notes.'.\$item->medicine_id)", $view);
        $this->assertStringContainsString('data-shortage-error', $view);
        $this->assertStringContainsString("setOrderStep(hasProductValidationErrors ? 2 : 1)", $view);
        $this->assertStringContainsString('scrollIntoView', $view);
        $this->assertStringContainsString('draft_shortage_notes.$medicineId', $service);
        $this->assertStringContainsString("'draft_shortage_notes' => ['sometimes', 'array']", $controller);
        $this->assertStringContainsString('draft_expected_supply_dates[{{ $item->medicine_id }}]', $view);
        $this->assertStringContainsString("'draft_expected_supply_dates.*' => ['nullable', 'date']", $controller);
        $this->assertStringContainsString("'draft_expected_supply_dates'] ?? []", $controller);
        $this->assertStringContainsString("'expected_supply_date' => \$supplyDate", $service);
        $this->assertStringContainsString("->deferredSupplies()->whereNull('drug_bid_award_allocation_id')->delete()", $service);
    }
}
