<?php

// Private hotel uniform JSON snapshots (no database)

// Standalone hotel uniform demo


// Equipment maintenance standalone demo





use App\Http\Controllers\Demo\SalesEmployeesController;
use App\Http\Controllers\Demo\SharedSidebarMenuController;
use App\Http\Controllers\Demo\SalesDemoDatasetController;
use App\Http\Controllers\Demo\WarehouseInventoryController;
use App\Http\Controllers\Demo\WarehouseDemoDatasetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\ApplicationRootRedirectService;

// Attendance Payroll routes moved to Modules/Demo/routes/web.php.

// HR routes are owned by Modules/Demo.

// Debt Management routes moved to Modules/Demo/routes/web.php.

// Standalone demo: a single GET route, with browser-only persistence.
Route::get('/demo/doanh-so-nhan-vien', SalesEmployeesController::class)
    ->name('demo.sales-employees');

Route::get('/demo/doanh-so-nhan-vien/datasets', [SalesDemoDatasetController::class, 'index'])->name('demo.sales-employees.datasets');
Route::get('/demo/doanh-so-nhan-vien/datasets/{dataset}', [SalesDemoDatasetController::class, 'show'])->name('demo.sales-employees.datasets.show');
Route::post('/demo/doanh-so-nhan-vien/datasets', [SalesDemoDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.sales-employees.datasets.store');

Route::get('/demo/quan-ly-xuat-nhap-ton', WarehouseInventoryController::class)
    ->name('demo.warehouse-inventory');

// Public demo datasets are read-only. Server backups require an operator-only secret.
Route::get('/demo/quan-ly-xuat-nhap-ton/datasets', [WarehouseDemoDatasetController::class, 'index'])
    ->name('demo.warehouse-inventory.datasets');
Route::get('/demo/quan-ly-xuat-nhap-ton/datasets/{dataset}', [WarehouseDemoDatasetController::class, 'show'])
    ->name('demo.warehouse-inventory.datasets.show');

Route::post('/demo/quan-ly-xuat-nhap-ton/datasets', [WarehouseDemoDatasetController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('demo.warehouse-inventory.datasets.store');


// Rental-room routes moved to Modules/Demo/routes/web.php.

// Route-cache-safe shared sidebar endpoints for two standalone demos.
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

// Product costing standalone demo; browser state and protected server snapshots.
Route::get('/demo/tinh-gia-thanh-san-pham', \App\Http\Controllers\Demo\ProductCostingController::class)->name('demo.product-costing');
Route::get('/demo/tinh-gia-thanh-san-pham/datasets', [\App\Http\Controllers\Demo\ProductCostingDatasetController::class,'index'])->middleware('throttle:20,1')->name('demo.product-costing.datasets');
Route::get('/demo/tinh-gia-thanh-san-pham/datasets/{dataset}', [\App\Http\Controllers\Demo\ProductCostingDatasetController::class,'show'])->middleware('throttle:20,1')->name('demo.product-costing.datasets.show');
Route::post('/demo/tinh-gia-thanh-san-pham/datasets', [\App\Http\Controllers\Demo\ProductCostingDatasetController::class,'store'])->middleware('throttle:5,1')->name('demo.product-costing.datasets.store');

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        $routeName = app(ApplicationRootRedirectService::class)->configuredRoute();

        return redirect()->route($routeName);
    }

    abort(404);
});



