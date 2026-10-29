<?php

// Private hotel uniform JSON snapshots (no database)

// Standalone hotel uniform demo


// Equipment maintenance standalone demo





use App\Http\Controllers\Demo\SharedSidebarMenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\System\Services\ApplicationRootRedirectService;

// Attendance Payroll routes moved to Modules/Demo/routes/web.php.

// HR routes are owned by Modules/Demo.

// Debt Management routes moved to Modules/Demo/routes/web.php.

// Sales Employees routes moved to Modules/Demo/routes/web.php.

// Warehouse inventory routes moved to Modules/Demo/routes/web.php.

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

Route::fallback(function (Request $request) {
    if ($request->path() === '/') {
        $routeName = app(ApplicationRootRedirectService::class)->configuredRoute();

        return redirect()->route($routeName);
    }

    abort(404);
});



