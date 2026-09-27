<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Pharma\Models\Medicine;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;
//use Illuminate\Http\Request;

class PharmaController extends Controller
{
    public function __construct()
    {
       // $this->middleware('permission:pharma-list|pharma-create|pharma-edit|pharma-delete', ['only' => ['index','show']]);
       // $this->middleware('permission:pharma-create', ['only' => ['create','store']]);
       // $this->middleware('permission:pharma-edit', ['only' => ['edit','update']]);
       // $this->middleware('permission:pharma-delete', ['only' => ['destroy']]);
    }

    public function index(): View
    {
        return view('Pharma::pages.index');
    }

    public function export(Request $request): StreamedResponse
    {
        $selectedIds = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $rows = Medicine::query()
            ->when($selectedIds->isNotEmpty(), fn ($query) => $query->whereKey($selectedIds->all()))
            ->with([
                'variants:id,medicine_id,sku,declared_price,is_default',
                'currentProfile' => fn ($query) => $query->select([
                    'pharma_medicine_profiles.id',
                    'pharma_medicine_profiles.medicine_id',
                    'pharma_medicine_profiles.profile_version',
                    'pharma_medicine_profiles.profile_status',
                    'pharma_medicine_profiles.is_current',
                ]),
                'supplierTrackings' => fn ($query) => $query
                    ->select(['id', 'medicine_id', 'partner_id', 'status'])
                    ->with('partner:id,name'),
            ])
            ->withCount(['sources', 'drugBidAwards'])
            ->latest('id')
            ->get()
            ->map(function (Medicine $medicine): array {
                $variant = $medicine->variants->firstWhere('is_default', true) ?? $medicine->variants->first();
                $suppliers = $medicine->supplierTrackings
                    ->pluck('partner.name')
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    'Mã thuốc' => $medicine->medicine_code,
                    'SKU' => $variant?->sku,
                    'Tên thuốc' => $medicine->name,
                    'GPLH' => $medicine->registration_number_primary ?: $medicine->registration_number,
                    'Nhóm thuốc theo thông tư' => $medicine->circular_group,
                    'Hoạt chất' => $medicine->active_ingredients,
                    'Hàm lượng' => $medicine->concentration,
                    'Dạng bào chế' => $medicine->dosage_form,
                    'Đường dùng' => $medicine->route_of_administration,
                    'Đơn vị tính' => $medicine->unit,
                    'Quy cách' => $medicine->packaging_specification,
                    'Giá kê khai' => $variant?->declared_price ?? $medicine->declared_price,
                    'Cơ sở đăng ký' => $medicine->registered_company,
                    'Cơ sở sản xuất' => $medicine->manufacturing_company,
                    'Nước sản xuất' => $medicine->manufacturing_country,
                    'Nhà cung cấp' => $suppliers->join(' | '),
                    'Chất lượng master' => $medicine->profile_status,
                    'HSSP' => $medicine->currentProfile ? 'Có' : 'Chưa có',
                    'Phiên bản HSSP' => $medicine->currentProfile?->profile_version,
                    'Số nguồn' => $medicine->sources_count,
                    'Số kết quả thầu' => $medicine->drug_bid_awards_count,
                ];
            });

        return (new FastExcel($rows))->download('pharma-danh-muc-thuoc-chuan-'.now()->format('Ymd-His').'.xlsx');
    }

    public function create(): View
    {
        return view('Pharma::pages.create');
    }

    public function edit(int $id): View
    {
        return view('Pharma::pages.edit', compact('id'));
    }
}
