<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DrugBidAwardController extends Controller
{
    /**
     * Hiển thị trang danh sách hồ sơ trúng thầu kèm bộ lọc.
     */
    public function index(): View
    {
        return view('Pharma::pages.drug-bid-award.index');
    }

    /**
     * Hiển thị form khởi tạo bản ghi trúng thầu mới.
     */
    public function create(): View
    {
        return view('Pharma::pages.drug-bid-award.create');
    }

    /**
     * Hiển thị form chỉnh sửa một bản ghi trúng thầu cụ thể.
     */
    public function edit(int $id): View
    {
        return view('Pharma::pages.drug-bid-award.edit', compact('id'));
    }
}
