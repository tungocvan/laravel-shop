<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\InventoryMovementSummaryService;
use Modules\Pharma\Services\InventoryService;
use Modules\Pharma\Services\UserInventoryWorkspace;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PharmaInventoryReportsController extends Controller
{
    private function authorizeInventory(Request $request, ApplicationRegistry $registry, bool $write = false): void
    {
        $user = $request->user('web');
        abort_unless($user && $registry->userCan($user, 'client.pharma.inventory.view'), 403);
        if ($write) {
            abort_unless($registry->userCan($user, 'client.pharma.inventory.opening.manage'), 403);
        }
    }

    public function movements(Request $request, ApplicationRegistry $registry, InventoryService $inventory, InventoryMovementSummaryService $summary): View
    {
        $this->authorizeInventory($request, $registry);
        $data = $request->validate([
            'from'=>'nullable|date', 'to'=>'nullable|date|after_or_equal:from',
            'movement_medicine_id'=>'nullable|integer|exists:pharma_medicines,id',
            'per_page'=>'nullable|integer|in:25,50,100',
        ]);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->endOfMonth()->toDateString())->endOfDay();
        $warehouse = $inventory->defaultWarehouse();
        $movementMedicineId = isset($data['movement_medicine_id']) ? (int)$data['movement_medicine_id'] : null;
        $movementPerPage = (int)($data['per_page'] ?? 25);
        $report = $summary->summarize($warehouse, $from, $to, $movementMedicineId, [], $movementPerPage);
        $medicineIds = InventoryBalance::query()->where('warehouse_id', $warehouse->id)->distinct()->pluck('medicine_id');
        $movementMedicines = Medicine::query()->whereIn('id', $medicineIds)->orderBy('name')->get(['id','medicine_code','name']);
        $canViewCosts = $registry->userCan($request->user('web'), 'client.pharma.inventory.costs');
        return view('ClientPortal::applications.pharma.inventory-movements', compact(
            'warehouse','from','to','report','movementMedicineId','movementPerPage','movementMedicines','canViewCosts'
        ));
    }

    public function exportMovements(Request $request, ApplicationRegistry $registry, InventoryService $inventory, InventoryMovementSummaryService $summary): StreamedResponse
    {
        $this->authorizeInventory($request, $registry);
        $data = $request->validate([
            'from'=>'required|date', 'to'=>'required|date|after_or_equal:from',
            'movement_medicine_id'=>'nullable|integer|exists:pharma_medicines,id',
            'ids'=>'nullable|array|max:500', 'ids.*'=>'integer|distinct|exists:pharma_inventory_balances,id',
        ]);
        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();
        $warehouse = $inventory->defaultWarehouse();
        $ids = $data['ids'] ?? [];
        if ($ids) {
            $allowed = InventoryBalance::query()->where('warehouse_id', $warehouse->id)->whereIn('id', $ids)->count();
            abort_unless($allowed === count($ids), 403);
        }
        $report = $summary->summarize($warehouse, $from, $to, isset($data['movement_medicine_id']) ? (int)$data['movement_medicine_id'] : null, $ids);
        $rows = $report['rows']->map(fn ($r) => [
            'Mã thuốc'=>$r->medicine_code, 'Tên thuốc'=>$r->name, 'Số lô'=>$r->batch_number,
            'Hạn dùng'=>Carbon::parse($r->expiry_date)->format('d/m/Y'),
            'Tồn đầu kỳ'=>(float)$r->period_opening, 'Nhập trong kỳ'=>(float)$r->period_in,
            'Xuất trong kỳ'=>(float)$r->period_out, 'Tồn cuối kỳ'=>(float)$r->period_closing,
        ]);
        return (new FastExcel($rows))->download('client-pharma-xuat-nhap-ton-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx');
    }

    public function exportStock(Request $request, ApplicationRegistry $registry, UserInventoryWorkspace $workspace, InventoryService $inventory): StreamedResponse
    {
        $this->authorizeInventory($request, $registry);
        $data = $request->validate([
            'ids'=>'nullable|array|max:500',
            'ids.*'=>'integer|distinct|exists:pharma_inventory_balances,id',
        ]);
        $canCosts = $registry->userCan($request->user('web'), 'client.pharma.inventory.costs');
        $selected = $data['ids'] ?? [];
        $rows = collect();
        if ($selected) {
            $warehouse = $inventory->defaultWarehouse();
            $balances = InventoryBalance::query()->with('medicine')
                ->where('warehouse_id', $warehouse->id)->whereIn('id', $selected)->get();
            abort_unless($balances->count() === count($selected), 403);
            foreach ($balances as $r) {
                $record = [
                    'Mã thuốc'=>$r->medicine->medicine_code, 'Tên thuốc'=>$r->medicine->name,
                    'Số lô'=>$r->batch_number, 'Hạn dùng'=>$r->expiry_date->format('d/m/Y'),
                    'Tồn hiện tại'=>(float)$r->quantity_on_hand,
                ];
                if ($canCosts) {
                    $record['Giá vốn'] = $r->average_cost_price === null ? null : (float)$r->average_cost_price;
                    $record['Giá trị tồn'] = $r->inventory_value === null ? null : (float)$r->inventory_value;
                }
                $rows->push($record);
            }
        } else {
            // Preserve the existing full-export behavior for callers without selected IDs.
            for ($page = 1; ; $page++) {
                $batch = $workspace->browse(perPage: 100, page: $page, canViewCosts: $canCosts);
                foreach ($batch->items() as $r) {
                    $record = [
                        'Mã thuốc'=>$r->medicine->medicine_code, 'Tên thuốc'=>$r->medicine->name,
                        'Số lô'=>$r->batch_number, 'Hạn dùng'=>$r->expiry_date->format('d/m/Y'),
                        'Tồn hiện tại'=>(float)$r->quantity_on_hand,
                    ];
                    if ($canCosts) {
                        $record['Giá vốn'] = $r->average_cost_price === null ? null : (float)$r->average_cost_price;
                        $record['Giá trị tồn'] = $r->inventory_value === null ? null : (float)$r->inventory_value;
                    }
                    $rows->push($record);
                }
                if (! $batch->hasMorePages()) break;
            }
        }
        return (new FastExcel($rows))->download('client-pharma-ton-kho-'.now()->format('Ymd-His').'.xlsx');
    }

    public function opening(Request $request, ApplicationRegistry $registry): View
    {
        $this->authorizeInventory($request, $registry, true);
        $medicines = Medicine::query()->orderBy('name')->get(['id','name','medicine_code']);
        return view('ClientPortal::applications.pharma.inventory-opening', compact('medicines'));
    }

    public function storeOpeningManual(Request $request, ApplicationRegistry $registry, InventoryService $inventory): RedirectResponse
    {
        $this->authorizeInventory($request, $registry, true);
        $data = $request->validate([
            'medicine_id'=>'required|integer|exists:pharma_medicines,id',
            'batch_number'=>'required|string|max:100',
            'expiry_date'=>'required|date',
            'opening_quantity'=>'required|numeric|gt:0',
        ]);
        $warehouse = $inventory->defaultWarehouse();
        DB::transaction(function () use ($data, $warehouse, $inventory) {
            $existing = InventoryBalance::query()->where('warehouse_id', $warehouse->id)
                ->where('medicine_id', $data['medicine_id'])->where('batch_number', $data['batch_number'])
                ->whereDate('expiry_date', $data['expiry_date'])->lockForUpdate()->first();
            $hasLedger = DB::table('pharma_inventory_transactions')->where('warehouse_id', $warehouse->id)
                ->where('medicine_id', $data['medicine_id'])->where('batch_number', $data['batch_number'])
                ->whereDate('expiry_date', $data['expiry_date'])->exists();
            if ($hasLedger || ($existing && ((float)$existing->opening_quantity !== 0.0 || (float)$existing->quantity_on_hand !== 0.0))) {
                throw ValidationException::withMessages(['batch_number'=>'Lô đã phát sinh tồn, không thể ghi nhận tồn đầu kỳ lần nữa.']);
            }
            $inventory->setOpeningBalance($warehouse->id, (int)$data['medicine_id'], $data['batch_number'], $data['expiry_date'], (float)$data['opening_quantity'], null);
        });
        return redirect()->route('client.pharma.inventory.opening')->with('success', 'Đã ghi nhận tồn đầu kỳ cho lô '.$data['batch_number'].'.');
    }

    public function openingTemplate(Request $request, ApplicationRegistry $registry): StreamedResponse
    {
        $this->authorizeInventory($request, $registry, true);
        return (new FastExcel(collect([[
            'Ma thuoc'=>'MED-000001', 'So lo'=>'LO-001', 'Han dung'=>'2027-12-31', 'Ton dau ky'=>100,
        ]])))->download('client-pharma-ton-dau-ky-mau.xlsx');
    }

    public function importOpening(Request $request, ApplicationRegistry $registry, InventoryService $inventory): RedirectResponse
    {
        $this->authorizeInventory($request, $registry, true);
        $request->validate(['file'=>'required|file|mimes:xlsx,xls,csv|max:10240']);
        $raw = (new FastExcel)->import($request->file('file')->getRealPath());
        if ($raw->isEmpty()) throw ValidationException::withMessages(['file'=>'File không có dữ liệu.']);
        if ($raw->count() > 1000) throw ValidationException::withMessages(['file'=>'Tối đa 1.000 dòng mỗi lần nhập.']);
        $rows = $raw->values()->map(function ($r, $i) {
            $code = trim((string)($r['Ma thuoc'] ?? ''));
            $lot = trim((string)($r['So lo'] ?? ''));
            $date = trim((string)($r['Han dung'] ?? ''));
            $qty = $r['Ton dau ky'] ?? null;
            if ($code === '' || $lot === '' || mb_strlen($lot) > 100 || ! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date) || ! checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)) || ! is_numeric($qty) || (float)$qty <= 0) {
                throw ValidationException::withMessages(['file'=>'Dòng '.($i+2).': cần Mã thuốc, Số lô, Hạn dùng YYYY-MM-DD và Tồn đầu kỳ > 0.']);
            }
            return ['code'=>$code,'lot'=>$lot,'date'=>$date,'qty'=>(float)$qty];
        });
        if ($rows->map(fn ($r)=>$r['code'].'|'.$r['lot'].'|'.$r['date'])->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['file'=>'File chứa dòng trùng Mã thuốc + Số lô + Hạn dùng.']);
        }
        $medicines = Medicine::query()->whereIn('medicine_code', $rows->pluck('code')->unique())->pluck('id','medicine_code');
        foreach ($rows as $row) {
            if (! $medicines->has($row['code'])) throw ValidationException::withMessages(['file'=>'Mã thuốc không tồn tại: '.$row['code']]);
        }
        $warehouse = $inventory->defaultWarehouse();
        DB::transaction(function () use ($rows, $medicines, $warehouse, $inventory) {
            // Validate all lots before any writes; a later conflict must roll back the whole file.
            foreach ($rows as $row) {
                $existing = InventoryBalance::query()->where('warehouse_id', $warehouse->id)
                    ->where('medicine_id', $medicines[$row['code']])->where('batch_number', $row['lot'])
                    ->whereDate('expiry_date', $row['date'])->lockForUpdate()->first();
                $hasLedger = DB::table('pharma_inventory_transactions')->where('warehouse_id', $warehouse->id)
                    ->where('medicine_id', $medicines[$row['code']])->where('batch_number', $row['lot'])
                    ->whereDate('expiry_date', $row['date'])->exists();
                if ($hasLedger || ($existing && ((float)$existing->opening_quantity !== 0.0 || (float)$existing->quantity_on_hand !== 0.0))) {
                    throw ValidationException::withMessages(['file'=>'Lô '.$row['lot'].' đã phát sinh tồn, không thể nhập lại.']);
                }
            }
            foreach ($rows as $row) {
                $inventory->setOpeningBalance($warehouse->id, (int)$medicines[$row['code']], $row['lot'], $row['date'], $row['qty'], null);
            }
        });
        return redirect()->route('client.pharma.inventory.opening')->with('success','Đã nhập '.count($rows).' dòng tồn đầu kỳ.');
    }
}
