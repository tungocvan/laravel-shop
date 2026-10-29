<?php

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomAssetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomDatasetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomManagementController;

Route::get('/demo-assets/quan-ly-phong-tro/{asset}', RentalRoomAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js|shared/theme-selector\\.js')
    ->name('demo.rental-room-management.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-phong-tro', RentalRoomManagementController::class)->name('demo.rental-room-management');
    Route::get('/demo/quan-ly-phong-tro/datasets', [RentalRoomDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.rental-room-management.datasets');
    Route::get('/demo/quan-ly-phong-tro/datasets/{dataset}', [RentalRoomDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.rental-room-management.datasets.show');
    Route::post('/demo/quan-ly-phong-tro/datasets', [RentalRoomDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.rental-room-management.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformController;
use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformDatasetController;
use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformAssetController;

Route::get('/demo-assets/quan-ly-dong-phuc-khach-san/{asset}', HotelUniformAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.hotel-uniform.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-dong-phuc-khach-san', HotelUniformController::class)->name('demo.hotel-uniform');
    Route::get('/demo/quan-ly-dong-phuc-khach-san/datasets', [HotelUniformDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.hotel-uniform.datasets');
    Route::get('/demo/quan-ly-dong-phuc-khach-san/datasets/{dataset}', [HotelUniformDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.hotel-uniform.datasets.show');
    Route::post('/demo/quan-ly-dong-phuc-khach-san/datasets', [HotelUniformDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.hotel-uniform.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceController;
use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceDatasetController;
use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceAssetController;

Route::get('/demo-assets/quan-ly-thiet-bi-bao-tri/{asset}', EquipmentMaintenanceAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.equipment-maintenance.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-thiet-bi-bao-tri', EquipmentMaintenanceController::class)->name('demo.equipment-maintenance');
    Route::get('/demo/quan-ly-thiet-bi-bao-tri/datasets', [EquipmentMaintenanceDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.equipment-maintenance.datasets');
    Route::get('/demo/quan-ly-thiet-bi-bao-tri/datasets/{dataset}', [EquipmentMaintenanceDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.equipment-maintenance.datasets.show');
    Route::post('/demo/quan-ly-thiet-bi-bao-tri/datasets', [EquipmentMaintenanceDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.equipment-maintenance.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\CoffeeShopSales\CoffeeShopSalesController;
use Modules\Demo\Http\Controllers\Projects\CoffeeShopSales\CoffeeShopSalesDatasetController;
use Modules\Demo\Http\Controllers\Projects\CoffeeShopSales\CoffeeShopSalesAssetController;

Route::get('/demo-assets/quan-ly-ban-hang-ca-phe/{asset}', CoffeeShopSalesAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.coffee-shop-sales.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-ban-hang-ca-phe', CoffeeShopSalesController::class)->name('demo.coffee-shop-sales');
    Route::get('/demo/quan-ly-ban-hang-ca-phe/datasets', [CoffeeShopSalesDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.coffee-shop-sales.datasets');
    Route::get('/demo/quan-ly-ban-hang-ca-phe/datasets/{dataset}', [CoffeeShopSalesDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.coffee-shop-sales.datasets.show');
    Route::post('/demo/quan-ly-ban-hang-ca-phe/datasets', [CoffeeShopSalesDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.coffee-shop-sales.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\DailyWorkReport\DailyWorkReportController;
use Modules\Demo\Http\Controllers\Projects\DailyWorkReport\DailyWorkReportAssetController;

Route::get('/demo-assets/bao-cao-cong-viec-hang-ngay/{asset}', DailyWorkReportAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|original\\.html')
    ->name('demo.daily-work-report.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/bao-cao-cong-viec-hang-ngay', DailyWorkReportController::class)->name('demo.daily-work-report');
});

use Modules\Demo\Http\Controllers\Projects\EnterpriseManagementReport\EnterpriseManagementReportController;
use Modules\Demo\Http\Controllers\Projects\EnterpriseManagementReport\EnterpriseManagementReportAssetController;

Route::get('/demo-assets/bao-cao-quan-tri-doanh-nghiep/{asset}', EnterpriseManagementReportAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/preferences\\.js|original\\.html')
    ->name('demo.enterprise-management-report.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/bao-cao-quan-tri-doanh-nghiep', EnterpriseManagementReportController::class)
        ->name('demo.enterprise-management-report');
});


use Modules\Demo\Http\Controllers\Projects\AttendancePayroll\AttendancePayrollController;
use Modules\Demo\Http\Controllers\Projects\AttendancePayroll\AttendancePayrollDatasetController;
use Modules\Demo\Http\Controllers\Projects\AttendancePayroll\AttendancePayrollAssetController;

Route::get('/demo-assets/cham-cong-tinh-luong/{asset}', AttendancePayrollAssetController::class)
    ->where('asset', 'css/app\\.css|css/extensions\\.css|js/core\\.js|js/payroll\\.js|js/extensions\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.attendance-payroll.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/cham-cong-tinh-luong', AttendancePayrollController::class)->name('demo.attendance-payroll');
    Route::get('/demo/cham-cong-tinh-luong/datasets', [AttendancePayrollDatasetController::class, 'index'])->name('demo.attendance-payroll.datasets');
    Route::get('/demo/cham-cong-tinh-luong/datasets/{dataset}', [AttendancePayrollDatasetController::class, 'show'])->name('demo.attendance-payroll.datasets.show');
    Route::post('/demo/cham-cong-tinh-luong/datasets', [AttendancePayrollDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.attendance-payroll.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\HumanResources\HumanResourcesController;
use Modules\Demo\Http\Controllers\Projects\HumanResources\HumanResourcesDatasetController;
use Modules\Demo\Http\Controllers\Projects\HumanResources\HumanResourcesAssetController;

Route::get('/demo-assets/quan-ly-nhan-su/{asset}', HumanResourcesAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/menu-adapter\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.human-resources.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-nhan-su', HumanResourcesController::class)->name('demo.human-resources');
    Route::get('/demo/quan-ly-nhan-su/datasets', [HumanResourcesDatasetController::class, 'index'])->name('demo.human-resources.datasets');
    Route::get('/demo/quan-ly-nhan-su/datasets/{dataset}', [HumanResourcesDatasetController::class, 'show'])->name('demo.human-resources.datasets.show');
    Route::post('/demo/quan-ly-nhan-su/datasets', [HumanResourcesDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.human-resources.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\DebtManagement\DebtManagementController;
use Modules\Demo\Http\Controllers\Projects\DebtManagement\DebtManagementDatasetController;
use Modules\Demo\Http\Controllers\Projects\DebtManagement\DebtManagementMenuController;
use Modules\Demo\Http\Controllers\Projects\DebtManagement\DebtManagementAssetController;

Route::get('/demo-assets/quan-ly-cong-no/{asset}', DebtManagementAssetController::class)
    ->where('asset', 'css/app\\.css|js/core\\.js|js/menu-builder\\.js|js/app\\.js|js/server-datasets\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.debt-management.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-cong-no', DebtManagementController::class)->name('demo.debt-management');
    Route::get('/demo/quan-ly-cong-no/menu/presets', [DebtManagementMenuController::class, 'presets'])->name('demo.debt-management.menu.presets');
    Route::get('/demo/quan-ly-cong-no/menu/presets/{preset}', [DebtManagementMenuController::class, 'preset'])->name('demo.debt-management.menu.presets.show');
    Route::delete('/demo/quan-ly-cong-no/menu/presets/{preset}', [DebtManagementMenuController::class, 'deletePreset'])->middleware('throttle:5,1')->name('demo.debt-management.menu.presets.delete');
    Route::put('/demo/quan-ly-cong-no/menu/presets/{preset}', [DebtManagementMenuController::class, 'updatePreset'])->middleware('throttle:5,1')->name('demo.debt-management.menu.presets.update');
    Route::post('/demo/quan-ly-cong-no/menu/presets', [DebtManagementMenuController::class, 'savePreset'])->middleware('throttle:5,1')->name('demo.debt-management.menu.presets.store');
    Route::get('/demo/quan-ly-cong-no/menu', [DebtManagementMenuController::class, 'show'])->name('demo.debt-management.menu');
    Route::post('/demo/quan-ly-cong-no/menu', [DebtManagementMenuController::class, 'store'])->middleware('throttle:5,1')->name('demo.debt-management.menu.store');
    Route::get('/demo/quan-ly-cong-no/datasets', [DebtManagementDatasetController::class, 'index'])->name('demo.debt-management.datasets');
    Route::get('/demo/quan-ly-cong-no/datasets/{dataset}', [DebtManagementDatasetController::class, 'show'])->name('demo.debt-management.datasets.show');
    Route::post('/demo/quan-ly-cong-no/datasets', [DebtManagementDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.debt-management.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\SalesEmployees\SalesEmployeesController;
use Modules\Demo\Http\Controllers\Projects\SalesEmployees\SalesDemoDatasetController;
use Modules\Demo\Http\Controllers\Projects\SalesEmployees\SalesEmployeesAssetController;

Route::get('/demo-assets/doanh-so-nhan-vien/{asset}', SalesEmployeesAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.sales-employees.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/doanh-so-nhan-vien', SalesEmployeesController::class)->name('demo.sales-employees');
    Route::get('/demo/doanh-so-nhan-vien/datasets', [SalesDemoDatasetController::class, 'index'])->name('demo.sales-employees.datasets');
    Route::get('/demo/doanh-so-nhan-vien/datasets/{dataset}', [SalesDemoDatasetController::class, 'show'])->name('demo.sales-employees.datasets.show');
    Route::post('/demo/doanh-so-nhan-vien/datasets', [SalesDemoDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.sales-employees.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\WarehouseInventory\WarehouseInventoryController;
use Modules\Demo\Http\Controllers\Projects\WarehouseInventory\WarehouseDemoDatasetController;
use Modules\Demo\Http\Controllers\Projects\WarehouseInventory\WarehouseInventoryAssetController;

Route::get('/demo-assets/quan-ly-xuat-nhap-ton/{asset}', WarehouseInventoryAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.warehouse-inventory.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-xuat-nhap-ton', WarehouseInventoryController::class)->name('demo.warehouse-inventory');
    Route::get('/demo/quan-ly-xuat-nhap-ton/datasets', [WarehouseDemoDatasetController::class, 'index'])->name('demo.warehouse-inventory.datasets');
    Route::get('/demo/quan-ly-xuat-nhap-ton/datasets/{dataset}', [WarehouseDemoDatasetController::class, 'show'])->name('demo.warehouse-inventory.datasets.show');
    Route::post('/demo/quan-ly-xuat-nhap-ton/datasets', [WarehouseDemoDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.warehouse-inventory.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\ProductCosting\ProductCostingController;
use Modules\Demo\Http\Controllers\Projects\ProductCosting\ProductCostingDatasetController;
use Modules\Demo\Http\Controllers\Projects\ProductCosting\ProductCostingAssetController;

Route::get('/demo-assets/tinh-gia-thanh-san-pham/{asset}', ProductCostingAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.product-costing.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/tinh-gia-thanh-san-pham', ProductCostingController::class)->name('demo.product-costing');
    Route::get('/demo/tinh-gia-thanh-san-pham/datasets', [ProductCostingDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.product-costing.datasets');
    Route::get('/demo/tinh-gia-thanh-san-pham/datasets/{dataset}', [ProductCostingDatasetController::class, 'show'])->middleware('throttle:20,1')->name('demo.product-costing.datasets.show');
    Route::post('/demo/tinh-gia-thanh-san-pham/datasets', [ProductCostingDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.product-costing.datasets.store');
});


// Shared sidebar menu endpoints, owned by Modules/Demo.
use Modules\Demo\Http\Controllers\Shared\SharedSidebarMenuController;

Route::middleware('web')->group(function (): void {
Route::prefix('/demo/{demo}/menu')->group(function () {
    Route::put('/presets/{preset}/default', [SharedSidebarMenuController::class, 'setDefaultPreset'])->middleware('throttle:5,1')->where('demo', 'quan-ly-thiet-bi-bao-tri|quan-ly-dong-phuc-khach-san|quan-ly-phong-tro')->where('preset', '[a-z0-9-]{1,80}');
    Route::get('/', [SharedSidebarMenuController::class, 'show'])->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton|quan-ly-nhan-su|cham-cong-tinh-luong|bao-cao-quan-tri-doanh-nghiep|bao-cao-cong-viec-hang-ngay|quan-ly-ban-hang-ca-phe|tinh-gia-thanh-san-pham|quan-ly-thiet-bi-bao-tri|quan-ly-dong-phuc-khach-san|quan-ly-phong-tro');
    Route::post('/', [SharedSidebarMenuController::class, 'store'])->middleware('throttle:5,1')->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton|quan-ly-nhan-su|cham-cong-tinh-luong|bao-cao-quan-tri-doanh-nghiep|bao-cao-cong-viec-hang-ngay|quan-ly-ban-hang-ca-phe|tinh-gia-thanh-san-pham|quan-ly-thiet-bi-bao-tri|quan-ly-dong-phuc-khach-san|quan-ly-phong-tro');
    Route::get('/presets', [SharedSidebarMenuController::class, 'index'])->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton|quan-ly-nhan-su|cham-cong-tinh-luong|bao-cao-quan-tri-doanh-nghiep|bao-cao-cong-viec-hang-ngay|quan-ly-ban-hang-ca-phe|tinh-gia-thanh-san-pham|quan-ly-thiet-bi-bao-tri|quan-ly-dong-phuc-khach-san|quan-ly-phong-tro');
    Route::get('/presets/{preset}', [SharedSidebarMenuController::class, 'preset'])->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton|quan-ly-nhan-su|cham-cong-tinh-luong|bao-cao-quan-tri-doanh-nghiep|bao-cao-cong-viec-hang-ngay|quan-ly-ban-hang-ca-phe|tinh-gia-thanh-san-pham|quan-ly-thiet-bi-bao-tri|quan-ly-dong-phuc-khach-san|quan-ly-phong-tro');
    Route::post('/presets', [SharedSidebarMenuController::class, 'savePreset'])->middleware('throttle:5,1')->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton|quan-ly-nhan-su|cham-cong-tinh-luong|bao-cao-quan-tri-doanh-nghiep|bao-cao-cong-viec-hang-ngay|quan-ly-ban-hang-ca-phe|tinh-gia-thanh-san-pham|quan-ly-thiet-bi-bao-tri|quan-ly-dong-phuc-khach-san|quan-ly-phong-tro');
    Route::delete('/presets/{preset}', [SharedSidebarMenuController::class, 'deletePreset'])->middleware('throttle:5,1')->where('demo', 'doanh-so-nhan-vien|quan-ly-xuat-nhap-ton|quan-ly-nhan-su|cham-cong-tinh-luong|bao-cao-quan-tri-doanh-nghiep|bao-cao-cong-viec-hang-ngay|quan-ly-ban-hang-ca-phe|tinh-gia-thanh-san-pham|quan-ly-thiet-bi-bao-tri|quan-ly-dong-phuc-khach-san|quan-ly-phong-tro');
    Route::put('/presets/{preset}', [SharedSidebarMenuController::class, 'updatePreset'])->middleware('throttle:5,1')->where('demo', 'bao-cao-quan-tri-doanh-nghiep|bao-cao-cong-viec-hang-ngay|quan-ly-ban-hang-ca-phe|tinh-gia-thanh-san-pham|quan-ly-thiet-bi-bao-tri|quan-ly-dong-phuc-khach-san|quan-ly-phong-tro');
});
});
