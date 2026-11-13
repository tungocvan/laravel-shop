<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaGlobalDraftDeleteContractTest extends TestCase
{
    public function test_global_draft_delete_requires_approver_and_is_not_scoped_to_manager(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow = file_get_contents(base_path('Modules/Pharma/Services/UserPriceListWorkflow.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-lists.blade.php'));

        $this->assertStringContainsString("if (\$list->type === PriceList::TYPE_GLOBAL && \$list->status === PriceList::STATUS_DRAFT)", $controller);
        $this->assertStringContainsString('abort_unless($canApprove, 403);', $controller);
        $this->assertStringContainsString('$workflow->deleteGlobalDraft($priceList);', $controller);
        $this->assertStringContainsString('function deleteGlobalDraft(int $priceListId): void', $workflow);
        $this->assertStringContainsString('lockForUpdate()->findOrFail($priceListId)', $workflow);
        $this->assertStringContainsString('PriceList::TYPE_GLOBAL || $list->status !== PriceList::STATUS_DRAFT', $workflow);
        $this->assertStringContainsString('$list->delete();', $workflow);
        $this->assertStringContainsString('($priceList->type !== \'global\' || $canApprove)', $view);
        $this->assertStringContainsString('$errors->all()', $view);
    }
}
