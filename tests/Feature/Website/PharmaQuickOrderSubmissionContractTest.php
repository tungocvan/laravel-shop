<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class PharmaQuickOrderSubmissionContractTest extends TestCase
{
    public function test_submission_route_is_authenticated_and_csrf_protected(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $this->assertStringContainsString("middleware('auth')->post('/quick-order/requests'", $routes);
        $this->assertStringContainsString("'store'])->name('website.pharma.quick-order.requests.store')", $routes);
        $this->assertStringNotContainsString("withoutMiddleware([ValidateCsrfToken::class])->name('website.pharma.quick-order.requests.store')", $routes);
    }

    public function test_request_is_pending_review_and_does_not_create_inventory_issue(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/PharmaQuickOrderController.php'));
        $this->assertStringContainsString("'submission_key' => ['required', 'uuid']", $controller);
        $this->assertStringContainsString("'items.*.quantity' => ['required', 'integer', 'between:1,9999']", $controller);
        $this->assertStringContainsString('eligibleForCustomer(', $controller);
        $this->assertStringContainsString('PharmaQuickOrderRequest::PENDING_REVIEW', $controller);
        $this->assertStringNotContainsString('InventoryIssue::create(', $controller);
        $this->assertStringNotContainsString('createDraft(', $controller);
    }

    public function test_buyer_form_can_submit_and_review_starts_collapsed(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/pharma-quick-order.blade.php'));
        $this->assertStringContainsString('id="quick-order-submit"', $view);
        $this->assertStringContainsString("method: 'POST'", $view);
        $this->assertStringContainsString("'X-CSRF-TOKEN': csrfToken", $view);
        $this->assertStringContainsString("reviewToggle.setAttribute('aria-expanded', 'false')", $view);
        $this->assertStringContainsString('quick-order-back', $view);
    }
}
