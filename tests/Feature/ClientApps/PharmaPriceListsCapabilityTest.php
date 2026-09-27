<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaPriceListsCapabilityTest extends TestCase
{
    public function test_client_price_lists_are_scoped_to_managed_lists_without_admin_reuse(): void
    {
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/UserPriceListWorkspace.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-lists.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-show.blade.php'));
        $create = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-create.blade.php'));
        $workflow = file_get_contents(base_path('Modules/Pharma/Services/UserPriceListWorkflow.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/PriceList.php'));

        $this->assertStringContainsString("'route' => 'client.pharma.price-lists'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.price-lists.view'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.price-lists.create'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.price-lists.submit'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.price-lists.approve'", $manifest);
        $priceListFeature = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $priceListActions = $priceListFeature['features']['price-lists']['actions'] ?? [];
        $this->assertSame('client.pharma.price-lists.create', $priceListActions['create']['permission'] ?? null);
        $this->assertSame('client.pharma.price-lists.submit', $priceListActions['submit']['permission'] ?? null);
        $this->assertSame('client.pharma.price-lists.approve', $priceListActions['approve']['permission'] ?? null);

        $this->assertStringContainsString("->name('price-lists')", $routes);
        $this->assertStringContainsString("->name('price-lists.show')", $routes);
        $this->assertStringContainsString("->name('price-lists.create')", $routes);
        $this->assertStringContainsString("->name('price-lists.store')", $routes);
        $this->assertStringContainsString("->name('price-lists.submit')", $routes);
        $this->assertStringContainsString('client.feature:pharma,price-lists', $routes);
        $this->assertStringNotContainsString('auth:admin', $routes);

        $this->assertStringContainsString('UserPriceListWorkspace $workspace', $controller);
        $this->assertStringContainsString('$workspace->browse(', $controller);
        $this->assertStringContainsString('$workspace->findManaged(', $controller);
        $this->assertStringContainsString('abort_if($list === null, 404)', $controller);

        $this->assertStringContainsString("where('manager_user_id', \$userId)", $service);
        $this->assertStringContainsString('public function findManaged(int $userId, int $priceListId)', $service);
        $this->assertStringNotContainsString('auth(', $service);

        $this->assertStringContainsString('Bảng giá của tôi', $view);
        $this->assertStringContainsString('Chỉ hiển thị các bảng giá bạn là người phụ trách', $view);
        $this->assertStringContainsString('25,50,100', $view);
        $this->assertStringContainsString("setTimeout(()=>f.requestSubmit(),350)", $view);
        $this->assertStringNotContainsString('Admin::', $view);

        $this->assertStringContainsString('Giá kê khai', $detail);
        $this->assertStringContainsString('Giá bán CT', $detail);
        $this->assertStringContainsString('Giá thu / Giá HĐ', $detail);
        $this->assertStringContainsString('Gửi duyệt', $detail);
        $this->assertStringContainsString('STATUS_PENDING_APPROVAL', $detail);

        $this->assertStringContainsString('Tạo bảng giá cho khách hàng', $create);
        $this->assertStringContainsString('<x-search-select', $create);
        $this->assertStringContainsString('02 · Bảng giá gốc', $create);
        $this->assertStringContainsString('03 · Sản phẩm & giá', $create);
        $this->assertStringContainsString('04 · Kiểm tra & lưu', $create);
        $this->assertStringContainsString('source_price_list_id', $create);
        $this->assertStringNotContainsString('sticky bottom-4', $create);
        $this->assertStringContainsString('Lưu bảng giá Nháp', $create);
        $this->assertStringContainsString('name="selected[', $create);
        $this->assertStringContainsString('name="company_price[', $create);

        $this->assertStringContainsString("public const STATUS_PENDING_APPROVAL = 'pending_approval'", $model);
        $this->assertStringContainsString('public function createDraft(int $userId', $workflow);
        $this->assertStringContainsString('public function sourcePriceLists(int $userId)', $workflow);
        $this->assertStringContainsString('public function sourceProducts(int $userId, int $sourcePriceListId)', $workflow);
        $this->assertStringContainsString("->where('type', PriceList::TYPE_GLOBAL)", $workflow);
        $this->assertStringContainsString('->activeAt(now())', $workflow);
        $this->assertStringContainsString("whereDoesntHave('globalUsers')", $workflow);
        $this->assertStringContainsString("whereHas('globalUsers'", $workflow);
        $this->assertStringContainsString("'source_price_list_id'", $workflow);
        $this->assertStringContainsString("'declared_price_snapshot'", $workflow);
        $this->assertStringContainsString('public function submit(int $userId', $workflow);
        $this->assertStringContainsString("'manager_user_id'", $workflow);
        $this->assertStringContainsString("'submitted_by'", $workflow);
        $this->assertStringContainsString("'status' => PriceList::STATUS_PENDING_APPROVAL", $workflow);
        $this->assertStringNotContainsString('Admin::', $detail);
    }
}
