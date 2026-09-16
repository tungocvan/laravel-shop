<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class PriceListExcelJsonMediaRoundTripContractTest extends TestCase
{
    public function test_designer_labels_signing_date_and_defaults_only_when_empty(): void
    {
        $wrapper = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator-v32.blade.php'));

        $this->assertStringContainsString("node.textContent.trim() === 'Năm'", $wrapper);
        $this->assertStringContainsString("textNode.textContent = 'Ngày tháng năm'", $wrapper);
        $this->assertStringContainsString("input.value.trim() !== ''", $wrapper);
        $this->assertStringContainsString('Ngày ${pad(now.getDate())} tháng ${pad(now.getMonth() + 1)} năm ${now.getFullYear()}', $wrapper);
        $this->assertStringContainsString("new Event('input', { bubbles: true })", $wrapper);
    }

    public function test_profile_recovers_logo_but_signature_is_scoped_to_exact_signatory(): void
    {
        $model = file_get_contents(base_path('Modules/Pharma/Models/PriceListExportProfile.php'));

        $this->assertStringContainsString("where('user_id', \$profile->user_id)", $model);
        $this->assertStringContainsString("whereNotNull('logo_path')", $model);
        $this->assertStringContainsString("whereNotNull('signature_path')", $model);
        $this->assertStringContainsString("['signatory_title']", $model);
        $this->assertStringContainsString("['signatory_name']", $model);
        $this->assertStringContainsString('normalizeSignatory', $model);
        $this->assertStringContainsString("Storage::disk('public')->exists", $model);
        $this->assertStringContainsString('saveQuietly()', $model);
    }

    public function test_signature_recovery_does_not_fallback_to_another_person(): void
    {
        $model = file_get_contents(base_path('Modules/Pharma/Models/PriceListExportProfile.php'));

        $this->assertStringContainsString('=== $title', $model);
        $this->assertStringContainsString('=== $name', $model);
        $this->assertStringNotContainsString("latest('id')->value('signature_path')", $model);
    }
}
