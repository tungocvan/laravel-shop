<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaMedicineGovernanceContractTest extends TestCase
{
    #[Test]
    public function medicine_master_can_request_the_full_canonical_admin_container(): void
    {
        $content = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/content.blade.php'));
        $page = file_get_contents(base_path('Modules/Pharma/resources/views/pages/index.blade.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString("yieldContent('admin_container')", $content);
        $this->assertStringContainsString("'full' => 'w-full max-w-none'", $content);
        $this->assertStringContainsString("@section('admin_container', 'full')", $page);
        $this->assertStringContainsString('min-w-32', $view);
        $this->assertStringContainsString('whitespace-nowrap', $view);
    }

    #[Test]
    public function import_routes_probable_existing_medicines_to_review_instead_of_creating_duplicates(): void
    {
        $stager = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportStager.php'));

        $this->assertStringContainsString('possibleExistingMedicines(', $stager);
        $this->assertStringContainsString("'possible_existing_medicine_identity'", $stager);
        $this->assertStringContainsString("'possible_existing_medicine_ambiguous'", $stager);
        $this->assertStringContainsString('MedicineImportRow::CLASS_NEEDS_REVIEW', $stager);
    }

    #[Test]
    public function bid_linking_only_uses_verified_medicine_master_records(): void
    {
        $resolver = file_get_contents(base_path('Modules/Pharma/Services/MedicineIdentityResolver.php'));
        $matcher = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardMatcher.php'));
        $manager = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardMatchManager.php'));
        $review = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/ReviewWorkspace.php'));

        $this->assertStringContainsString("where('profile_status', Medicine::PROFILE_VERIFIED)", $resolver);
        $this->assertStringContainsString("where('profile_status', Medicine::PROFILE_VERIFIED)", $matcher);
        $this->assertStringContainsString('Chỉ được liên kết kết quả thầu với Medicine Master có Chất lượng master = Đã xác minh.', $manager);
        $this->assertStringContainsString("where('profile_status', Medicine::PROFILE_VERIFIED)", $review);
    }

    #[Test]
    public function unverified_medicine_cleanup_detaches_only_legacy_bid_links_and_keeps_hard_business_guards(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString("priceListItems()->exists()", $service);
        $this->assertStringContainsString("profile_status === Medicine::PROFILE_VERIFIED", $service);
        $this->assertStringContainsString("drugBidAwards()->update(['medicine_id' => null])", $service);
        $this->assertStringContainsString("filterRegistration", $livewire);
        $this->assertStringContainsString('Chưa có GPLH', $view);
        $this->assertStringContainsString('price_list_items_count', $view);
    }
}
