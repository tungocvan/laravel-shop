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
    private const EXPORT_COLUMNS = [
        'stt' => 'STT', 'medicine_code' => 'Medicine Code', 'medicine_name' => 'Tên thuốc',
        'active_ingredients' => 'Hoạt chất', 'strength' => 'Hàm lượng', 'registration_number' => 'GPLH',
        'sku' => 'SKU', 'package' => 'Quy cách', 'declared_price' => 'Giá kê khai',
        'bid_price' => 'Đơn giá trúng thầu', 'bid_quantity' => 'Số lượng trúng thầu',
        'bid_decision' => 'Số quyết định trúng thầu', 'bid_date' => 'Ngày trúng thầu',
        'bid_contractor' => 'Nhà thầu trúng thầu', 'bid_source' => 'Nguồn KQ trúng thầu',
        'company_sale_price' => 'Giá bán công ty', 'discount_percent' => '% CK thu',
        'actual_receivable_price' => 'Giá thu thực tế', 'invoice_price' => 'Giá xuất hóa đơn',
        'partner' => 'Khách hàng / loại', 'effective' => 'Hiệu lực', 'status' => 'Trạng thái dòng', 'note' => 'Ghi chú',
    ];

    private const DEFAULT_EXPORT_COLUMNS = ['stt','medicine_code','medicine_name','active_ingredients','strength','registration_number','sku','package','declared_price','bid_price','bid_date','company_sale_price','discount_percent','actual_receivable_price','invoice_price','partner','effective'];

    public function index(): View { return view('Pharma::pages.price-list.index'); }
    public function create(): View { return view('Pharma::pages.price-list.create'); }

    public function store(Request $request, PriceListManager $manager): RedirectResponse
    {
        $data = $manager->validateHeader($request->validate($this->headerRules()));
        $data['status'] = PriceList::STATUS_DRAFT; $data['created_by'] = auth('admin')->id();
        $priceList = PriceList::query()->create($data);
        return redirect()->route('admin.pharma.price-lists.edit', $priceList)->with('success', 'Đã tạo bảng giá nháp.');
    }

    public function show(PriceList $priceList): View
    {
        $priceList->load(['partner','manager','purpose','items.medicine','items.variant','items.package','items.bidEvidence']);
        $exportColumns = self::EXPORT_COLUMNS;
        $defaultExportColumns = self::DEFAULT_EXPORT_COLUMNS;
        return view('Pharma::pages.price-list.show', compact('priceList', 'exportColumns', 'defaultExportColumns'));
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

    public function activate(PriceList $priceList, PriceListManager $manager): RedirectResponse { $manager->activate($priceList, auth('admin')->id()); return back()->with('success', 'Bảng giá đã được kích hoạt.'); }
    public function deactivate(PriceList $priceList, PriceListManager $manager): RedirectResponse { $manager->deactivate($priceList); return back()->with('success', 'Bảng giá đã ngưng hiệu lực.'); }
    public function clone(PriceList $priceList, PriceListManager $manager): RedirectResponse { $copy = $manager->clone($priceList, ['name' => $priceList->name.' - Bản sao']); return redirect()->route('admin.pharma.price-lists.edit', $copy)->with('success', 'Đã clone thành bảng giá Draft mới.'); }

    public function export(Request $request, PriceList $priceList): BinaryFileResponse
    {
        $validated = $request->validate(['items' => ['nullable','array'], 'items.*' => ['integer'], 'columns' => ['nullable','array','min:1'], 'columns.*' => ['string','in:'.implode(',', array_keys(self::EXPORT_COLUMNS))]]);
        $selected = collect($validated['items'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $columns = collect($validated['columns'] ?? self::DEFAULT_EXPORT_COLUMNS)->filter(fn ($key) => isset(self::EXPORT_COLUMNS[$key]))->unique()->values()->all();
        abort_if($columns === [], 422, 'Vui lòng chọn ít nhất một cột để xuất Excel.');

        $query = $priceList->items()->with(['medicine','variant','package','bidEvidence']);
        if ($selected->isNotEmpty()) $query->whereKey($selected);
        $items = $query->orderBy('id')->get();

        $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bang gia');
        $sheet->fromArray([array_map(fn ($key) => self::EXPORT_COLUMNS[$key], $columns)], null, 'A1');
        foreach ($items as $index => $item) {
            $evidence = $item->bidEvidence;
            $discount = ($item->company_sale_price && $item->actual_receivable_price !== null)
                ? round((1 - ((float) $item->actual_receivable_price / (float) $item->company_sale_price)) * 100, 2) : null;
            $values = [
                'stt' => $index + 1, 'medicine_code' => $item->medicine?->medicine_code, 'medicine_name' => $item->medicine?->name,
                'active_ingredients' => $item->medicine?->active_ingredients, 'strength' => $item->variant?->strength_text ?: $item->medicine?->concentration,
                'registration_number' => $item->medicine?->registration_number, 'sku' => $item->variant?->sku,
                'package' => $item->package?->packaging_text ?: $item->medicine?->packaging_specification,
                'declared_price' => $item->declared_price_snapshot, 'bid_price' => $evidence?->bid_price, 'bid_quantity' => $evidence?->quantity,
                'bid_decision' => $evidence?->decision_number, 'bid_date' => $evidence?->award_date?->format('d/m/Y'),
                'bid_contractor' => $evidence?->contractor_name, 'bid_source' => $evidence?->source_system,
                'company_sale_price' => $item->company_sale_price, 'discount_percent' => $discount,
                'actual_receivable_price' => $item->actual_receivable_price, 'invoice_price' => $item->invoice_price,
                'partner' => $priceList->partner?->name ?: 'Bảng giá chung',
                'effective' => ($item->effective_from?->toDateString() ?? $priceList->effective_from?->toDateString() ?? '∞').' → '.($item->effective_to?->toDateString() ?? $priceList->effective_to?->toDateString() ?? '∞'),
                'status' => $item->status, 'note' => $item->note,
            ];
            $sheet->fromArray([array_map(fn ($key) => $values[$key] ?? null, $columns)], null, 'A'.($index + 2));
        }
        $sheet->freezePane('A2'); $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        foreach (range('A', $sheet->getHighestColumn()) as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);

        $path = storage_path('app/private/exports/price-lists/'.$priceList->code.'-'.now()->format('Ymd-His').'.xlsx');
        if (! is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
        (new Xlsx($spreadsheet))->save($path);
        return response()->download($path)->deleteFileAfterSend(true);
    }

    private function headerRules(): array
    {
        return ['code'=>['required','string','max:80'],'name'=>['required','string','max:255'],'type'=>['required','in:global,customer'],'partner_id'=>['nullable','integer'],'effective_from'=>['nullable','date'],'effective_to'=>['nullable','date'],'currency'=>['required','string','size:3'],'priority'=>['required','integer'],'notes'=>['nullable','string']];
    }
}
