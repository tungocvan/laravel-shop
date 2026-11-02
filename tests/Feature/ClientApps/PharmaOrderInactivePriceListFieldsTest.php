<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaOrderInactivePriceListFieldsTest extends TestCase
{
    public function test_only_selected_price_list_fields_are_enabled_for_submission(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php'));

        $this->assertStringContainsString("card.dataset.priceList===document.getElementById('price-list-select')?.value", $view);
        $this->assertStringContainsString("card.querySelectorAll('input,select,textarea').forEach(el=>el.disabled=!active)", $view);
        $this->assertStringContainsString("el.querySelectorAll('input,select,textarea').forEach(field=>field.disabled=source()!=='price_list'||!eligible)", $view);
        $this->assertStringContainsString("name=\"draft_shortage_notes[{{ \$item->medicine_id }}]\"", $view);
    }
}
