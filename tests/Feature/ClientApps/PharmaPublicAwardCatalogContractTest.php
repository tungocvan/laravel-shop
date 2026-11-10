<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaPublicAwardCatalogContractTest extends TestCase
{
    #[Test]
    public function public_tender_sources_are_kept_separate_from_canonical_awards(): void
    {
        $catalog = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalog.php'));
        $this->assertStringContainsString("'public_award_sources' => \$publicAwardSources", $catalog);
        $this->assertStringContainsString("'source_system', 'public_tender'", $catalog);
        $this->assertStringContainsString("'source_record_type', 'award_price'", $catalog);
        $this->assertStringContainsString("'is_active', true", $catalog);
        $this->assertStringContainsString("'awards' => \$recentAwards", $catalog);
        $this->assertStringContainsString("'origin' => 'canonical'", $catalog);
        $this->assertStringContainsString("'origin' => 'public_tender'", $catalog);
        $this->assertStringContainsString('->concat($publicAwardSources)', $catalog);
        $this->assertStringContainsString("'awarded' => (clone \$base)", $catalog);
    }

    #[Test]
    public function pwa_product_detail_labels_public_observations_without_modifying_prices(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/product-show.blade.php'));
        $this->assertStringContainsString("'publicAwardSources' => \$overview['public_award_sources']", $controller);
        $this->assertStringContainsString('@forelse($awards as $award)', $view);
        $this->assertStringContainsString("'awards' => \$recentAwards", file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalog.php')));
        $this->assertStringContainsString("'winningName'", file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalog.php')));
        $this->assertStringContainsString("'tenCdtBmt'", file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalog.php')));
        $this->assertStringContainsString("'ngayBanHanhQuyetDinh'", file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalog.php')));
        $this->assertStringContainsString('Thông tin trúng thầu gần đây', $view);
        $this->assertStringContainsString('Nguồn công khai · Chưa xác minh nghiệp vụ', $view);
        $this->assertStringContainsString('không thay đổi giá bán, giá nhập', $view);
        $this->assertSame(2, substr_count($view, "is_array(\$value) ? (implode('; ', array_filter(\$value, 'is_scalar'))"));
        $this->assertStringNotContainsString("filled(\$value) ? \$value : '—'", $view);
    }
}
