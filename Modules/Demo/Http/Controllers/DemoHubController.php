<?php

namespace Modules\Demo\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class DemoHubController extends Controller
{
    public function __invoke(): View
    {
        $demos = [
            ['title' => 'Quản lý phòng trọ', 'route' => 'demo.rental-room-management', 'description' => 'Phòng, khách thuê và hợp đồng'],
            ['title' => 'Quản lý đồng phục khách sạn', 'route' => 'demo.hotel-uniform', 'description' => 'Cấp phát và theo dõi đồng phục'],
            ['title' => 'Quản lý thiết bị bảo trì', 'route' => 'demo.equipment-maintenance', 'description' => 'Thiết bị và lịch bảo trì'],
            ['title' => 'Quản lý bán hàng cà phê', 'route' => 'demo.coffee-shop-sales', 'description' => 'Bán hàng và báo cáo'],
            ['title' => 'Báo cáo công việc hằng ngày', 'route' => 'demo.daily-work-report', 'description' => 'Ghi nhận và tổng hợp công việc'],
            ['title' => 'Báo cáo quản trị doanh nghiệp', 'route' => 'demo.enterprise-management-report', 'description' => 'Chỉ số và báo cáo quản trị'],
            ['title' => 'Chấm công tính lương', 'route' => 'demo.attendance-payroll', 'description' => 'Nhân sự, chấm công và tiền lương'],
            ['title' => 'Quản lý nhân sự', 'route' => 'demo.human-resources', 'description' => 'Hồ sơ và hoạt động nhân sự'],
            ['title' => 'Quản lý công nợ', 'route' => 'demo.debt-management', 'description' => 'Theo dõi công nợ phải thu, phải trả'],
            ['title' => 'Doanh số nhân viên', 'route' => 'demo.sales-employees', 'description' => 'Doanh số và hiệu quả bán hàng'],
            ['title' => 'Quản lý xuất nhập tồn', 'route' => 'demo.warehouse-inventory', 'description' => 'Nhập kho, xuất kho và tồn kho'],
            ['title' => 'Tính giá thành sản phẩm', 'route' => 'demo.product-costing', 'description' => 'Định mức, chi phí và giá thành'],
        ];

        return view('demo::hub.index', ['demos' => $demos]);
    }
}
