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
    ->where('asset', 'css/app\\.css|css/extensions\\.css|js/core\\.js|js/payroll\\.js|js/extensions\\.js')
    ->name('demo.attendance-payroll.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/cham-cong-tinh-luong', AttendancePayrollController::class)->name('demo.attendance-payroll');
    Route::get('/demo/cham-cong-tinh-luong/datasets', [AttendancePayrollDatasetController::class, 'index'])->name('demo.attendance-payroll.datasets');
    Route::get('/demo/cham-cong-tinh-luong/datasets/{dataset}', [AttendancePayrollDatasetController::class, 'show'])->name('demo.attendance-payroll.datasets.show');
    Route::post('/demo/cham-cong-tinh-luong/datasets', [AttendancePayrollDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.attendance-payroll.datasets.store');
});
