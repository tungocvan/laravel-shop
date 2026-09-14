<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Services\PriceListManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PriceListController extends Controller
{
    public function index(): View
    {
        return view('Pharma::pages.price-list.index');
    }

    public function create(): View
    {
        return view('Pharma::pages.price-list.create');
    }

    public function store(Request $request, PriceListManager $manager): RedirectResponse
    {
        $data = $manager->validateHeader($request->validate($this->headerRules()));
        $data['status'] = PriceList::STATUS_DRAFT;
        $data['created_by'] = auth('admin')->id();
        $priceList = PriceList::query()->create($data);

        return redirect()->route('admin.pharma.price-lists.edit', $priceList)->with('success', 'Đã tạo bảng giá nháp.');
    }

    public function show(PriceList $priceList): View
    {
        $priceList->load(['partner', 'items.medicine', 'items.variant', 'items.package']);

        return view('Pharma::pages.price-list.show', compact('priceList'));
    }

    public function edit(PriceList $priceList): View
    {
        abort_unless($priceList->isDraft(), 422, 'Chỉ bảng giá Draft mới được chỉnh trực tiếp. Hãy clone để tạo phiên bản mới.');

        return view('Pharma::pages.price-list.edit', compact('priceList'));
    }

    public function update(Request $request, PriceList $priceList, PriceListManager $manager): RedirectResponse
    {
        abort_unless($priceList->isDraft(), 422, 'Chỉ bảng giá Draft mới được chỉnh trực tiếp.');
        $priceList->update($manager->validateHeader($request->validate($this->headerRules()), $priceList));

        return back()->with('success', 'Đã cập nhật bảng giá.');
    }

    public function activate(PriceList $priceList, PriceListManager $manager): RedirectResponse
    {
        $manager->activate($priceList, auth('admin')->id());

        return back()->with('success', 'Bảng giá đã được kích hoạt.');
    }

    public function deactivate(PriceList $priceList, PriceListManager $manager): RedirectResponse
    {
        $manager->deactivate($priceList);

        return back()->with('success', 'Bảng giá đã ngưng hiệu lực.');
    }

    public function clone(PriceList $priceList, PriceListManager $manager): RedirectResponse
    {
        $copy = $manager->clone($priceList, ['name' => $priceList->name.' - Bản sao']);

        return redirect()->route('admin.pharma.price-lists.edit', $copy)->with('success', 'Đã clone thành bảng giá Draft mới.');
    }

    public function export(Request $request, PriceList $priceList): BinaryFileResponse
    {
        $selected = collect($request->query('items', []))->map(fn ($id) => (int) $id)->filter()->values();
        $query = $priceList->items()->with(['medicine', 'variant', 'package']);
        if ($selected->isNotEmpty()) {
            $query->whereKey($selected);
        }

        $items = $query->orderBy('id')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['STT', 'Medicine Code', 'Tên thuốc', 'Hoạt chất', 'Hàm lượng', 'GPLH', 'SKU', 'Quy cách', 'Giá kê khai', 'Giá bán công ty', 'Giá thu thực tế', 'Giá xuất hóa đơn', 'Khách hàng / loại', 'Hiệu lực'],
        ], null, 'A1');

        foreach ($items as $index => $item) {
            $sheet->fromArray([[
                $index + 1,
                $item->medicine?->medicine_code,
                $item->medicine?->name,
                $item->medicine?->active_ingredients,
                $item->variant?->strength_text ?: $item->medicine?->concentration,
                $item->medicine?->registration_number,
                $item->variant?->sku,
                $item->package?->packaging_text ?: $item->medicine?->packaging_specification,
                $item->declared_price_snapshot,
                $item->company_sale_price,
                $item->actual_receivable_price,
                $item->invoice_price,
                $priceList->partner?->name ?: 'Bảng giá chung',
                trim(($item->effective_from?->toDateString() ?? $priceList->effective_from?->toDateString() ?? '∞').' → '.($item->effective_to?->toDateString() ?? $priceList->effective_to?->toDateString() ?? '∞')),
            ]], null, 'A'.($index + 2));
        }

        $path = storage_path('app/private/exports/price-lists/'.$priceList->code.'-'.now()->format('Ymd-His').'.xlsx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    private function headerRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:global,customer'],
            'partner_id' => ['nullable', 'integer'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'size:3'],
            'priority' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
