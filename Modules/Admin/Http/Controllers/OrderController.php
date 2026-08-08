<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Website\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf; // Import thư viện PDF

class OrderController extends Controller
{
    public function index()
    {
        return view('Admin::pages.orders.index');
    }

    // Chúng ta sẽ làm trang chi tiết sau
    public function show($id)
    {
        return view('Admin::pages.orders.show', compact('id'));
    }
    // --- TÍNH NĂNG MỚI ---

    // 1. Xem và In trên trình duyệt (Print Preview)
    // Hàm xuất PDF
    public function exportPdf($id)
    {
        $order = Order::with('items')->findOrFail($id);

        // Truyền biến $isPdf = true để view biết mà ẩn nút
        $pdf = Pdf::loadView('Admin::pages.orders.invoice', compact('order') + ['isPdf' => true]);

        return $pdf->download('Invoice-' . $order->order_code . '.pdf');
    }

    // Hàm xem trên web (giữ nguyên hoặc truyền false)
    public function print($id)
    {
        $order = Order::with('items')->findOrFail($id);
        // Mặc định view không có biến $isPdf hoặc là false
        return view('Admin::pages.orders.invoice', compact('order'));
    }
}
