<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaDraftIssueShortageNoteContractTest extends TestCase
{
    public function test_draft_shortage_notes_are_validated_and_stored_per_medicine(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workflow = file_get_contents(base_path('Modules/Pharma/Services/UserOrderAuthoringService.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_02_150000_add_draft_shortage_note_to_pharma_inventory_issue_items.php'));

        $this->assertStringContainsString("'draft_shortage_notes.*' => ['nullable', 'string', 'max:2000']", $controller);
        $this->assertStringContainsString("['draft_shortage_notes'] ?? []", $controller);
        $this->assertStringContainsString("\$row['draft_shortage_note'] =", $workflow);
        $this->assertStringContainsString("if (! \$hasSufficientLot && \$note === '')", $workflow);
        $this->assertStringContainsString('ValidationException::withMessages', $workflow);
        $this->assertStringContainsString('draft_shortage_notes[{{ $item->medicine_id }}]', $view);
        $this->assertStringContainsString('$current?->draft_shortage_note', $view);
        $this->assertStringContainsString("text('draft_shortage_note')->nullable()", $migration);
    }
}
