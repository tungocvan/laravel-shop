<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaMedicineWorkspaceTest extends TestCase
{
    public function test_medicine_workspace_uses_bounded_page_sizes_without_all_mode(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $component);
        $this->assertStringNotContainsString("'All'", $component);
        $this->assertStringNotContainsString('999999', $component);
        $this->assertStringNotContainsString('Hiển thị tất cả', $view);
        $this->assertStringContainsString('@foreach ($perPageOptions as $option)', $view);
    }

    public function test_selection_is_page_scoped_and_resets_when_workspace_context_changes(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));

        $this->assertStringContainsString('public bool $selectPage = false;', $component);
        $this->assertStringContainsString('$this->selectedIds = $value ? $this->currentPageIds() : [];', $component);
        $this->assertStringContainsString("array_intersect(array_map('strval', \$this->selectedIds), \$pageIds)", $component);
        $this->assertStringContainsString('$this->resetWorkspacePage();', $component);
        $this->assertStringContainsString('$this->clearSelection();', $component);
    }

    public function test_workspace_exposes_profile_quality_and_source_award_counts(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString('public string $filterProfileStatus =', $component);
        $this->assertStringContainsString('Medicine::PROFILE_INCOMPLETE', $component);
        $this->assertStringContainsString('Medicine::PROFILE_NEEDS_REVIEW', $component);
        $this->assertStringContainsString("->withCount(['sources', 'drugBidAwards'])", $service);
        $this->assertStringContainsString("->when(\$profileStatus", $service);
        $this->assertStringContainsString('Data Quality filters', $view);
        $this->assertStringContainsString('{{ $medicine->sources_count }} nguồn', $view);
        $this->assertStringContainsString('{{ $medicine->drug_bid_awards_count }} kết quả trúng thầu', $view);
    }

    public function test_workspace_search_covers_product_identity_fields(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString("orWhere('registration_number', 'like'", $service);
        $this->assertStringContainsString("orWhere('concentration', 'like'", $service);
        $this->assertStringContainsString("orWhere('manufacturing_company', 'like'", $service);
        $this->assertStringContainsString("orWhere('manufacturing_country', 'like'", $service);
        $this->assertStringContainsString('SĐK', $view);
        $this->assertStringContainsString('NSX', $view);
    }

    public function test_destructive_and_export_controls_are_permission_aware(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString("\$canEdit = \$admin?->can('edit_pharma') ?? false;", $view);
        $this->assertStringContainsString("\$canDelete = \$admin?->can('delete_pharma') ?? false;", $view);
        $this->assertStringContainsString('wire:confirm="Xóa vĩnh viễn các hồ sơ thuốc đã chọn trên trang hiện tại?"', $view);
        $this->assertStringContainsString("'permission' => 'edit_pharma'", $view);
        $this->assertStringContainsString("'selected_ids' => \$selectedIds", $view);
        $this->assertStringContainsString("'profile_status' => \$filterProfileStatus", $view);
    }

    public function test_workspace_preserves_existing_named_routes_and_admin_navigation(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $page = file_get_contents(base_path('Modules/Pharma/resources/views/pages/index.blade.php'));

        $this->assertStringContainsString("route('admin.pharma.hssp.create')", $view);
        $this->assertStringContainsString("route('admin.pharma.hssp.edit', \$medicine->id)", $view);
        $this->assertStringContainsString('MedicineImportExport::class', $view);
        $this->assertStringContainsString("route('admin.pharma.dashboard')", $page);
        $this->assertStringContainsString('Quay về Dashboard Pharma', $page);
    }

    public function test_pharma_pages_share_a_dashboard_back_navigation(): void
    {
        $partial = file_get_contents(base_path('Modules/Pharma/resources/views/pages/partials/dashboard-back.blade.php'));
        $pages = [
            'Modules/Pharma/resources/views/pages/create.blade.php',
            'Modules/Pharma/resources/views/pages/edit.blade.php',
            'Modules/Pharma/resources/views/pages/drug-bid-award/index.blade.php',
            'Modules/Pharma/resources/views/pages/drug-bid-award/create.blade.php',
            'Modules/Pharma/resources/views/pages/drug-bid-award/edit.blade.php',
            'Modules/Pharma/resources/views/pages/supplier-trackings/index.blade.php',
            'Modules/Pharma/resources/views/pages/supplier-trackings/create.blade.php',
            'Modules/Pharma/resources/views/pages/supplier-trackings/edit.blade.php',
            'Modules/Pharma/resources/views/pages/supplier-trackings/show.blade.php',
            'Modules/Pharma/resources/views/pages/price-list/create.blade.php',
        ];

        $this->assertStringContainsString("route('admin.pharma.dashboard')", $partial);
        $this->assertStringContainsString('Quay về Dashboard Pharma', $partial);

        foreach ($pages as $page) {
            $this->assertStringContainsString(
                "@include('Pharma::pages.partials.dashboard-back')",
                file_get_contents(base_path($page)),
                "Expected {$page} to expose Pharma dashboard navigation."
            );
        }
    }
}
