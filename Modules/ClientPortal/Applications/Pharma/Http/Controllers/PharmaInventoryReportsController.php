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
            'medicine_code'=>$r->medicine_code, 'name'=>$r->name, 'unit'=>Medicine::query()->where('medicine_code', $r->medicine_code)->value('unit'), 'batch_number'=>$r->batch_number,
            'expiry_date'=>Carbon::parse($r->expiry_date)->format('d/m/Y'),
            'period_opening'=>(float)$r->period_opening, 'period_in'=>(float)$r->period_in,
            'period_out'=>(float)$r->period_out, 'period_closing'=>(float)$r->period_closing,
        ]);
        return $this->configuredDownload($request, $registry, 'movements', $rows, 'client-pharma-xuat-nhap-ton-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx');
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
        $warehouse = $inventory->defaultWarehouse();
        if ($selected) {
            $validCount = InventoryBalance::query()->where('warehouse_id', $warehouse->id)
                ->whereIn('id', $selected)->count();
            abort_unless($validCount === count($selected), 403);
        }
        // Use the same priced query as the stock UI for both selected and full exports.
        // InventoryBalance itself has no computed average_cost_price / inventory_value columns.
        $selectedSet = array_fill_keys(array_map('intval', $selected), true);
        for ($page = 1; ; $page++) {
            $batch = $workspace->browse(perPage: 100, page: $page, canViewCosts: $canCosts);
            foreach ($batch->items() as $r) {
                if ($selected && ! isset($selectedSet[(int) $r->id])) continue;
                $record = [
                    'medicine_code' => $r->medicine->medicine_code,
                    'name' => $r->medicine->name,
                    'unit' => $r->medicine->unit,
                    'batch_number' => $r->batch_number,
                    'expiry_date' => $r->expiry_date->format('d/m/Y'),
                    'quantity_on_hand' => (float) $r->quantity_on_hand,
                ];
                if ($canCosts) {
                    $record['average_cost_price'] = $r->average_cost_price === null ? null : (float) $r->average_cost_price;
                    $record['inventory_value'] = $r->inventory_value === null ? null : (float) $r->inventory_value;
                }
                $rows->push($record);
            }
            if (! $batch->hasMorePages()) break;
        }
        return $this->configuredDownload($request, $registry, 'stock', $rows, 'client-pharma-ton-kho-'.now()->format('Ymd-His').'.xlsx');
    }

    private function configuredDownload(Request $request, ApplicationRegistry $registry, string $report, iterable $rows, string $filename): StreamedResponse
    {
        $id = (int) $request->user('web')->id;
        $profileId = $request->validate(['profile_id' => 'nullable|integer'])['profile_id'] ?? null;
        $profile = PharmaInventoryExcelProfileController::profile($id, $report, $profileId);
        $allowed = PharmaInventoryExcelProfileController::allowed($report, $registry->userCan($request->user('web'), 'client.pharma.inventory.costs'));
        $columns = $profile ? array_values(array_intersect($profile->columns ?? [], array_keys($allowed))) : array_keys($allowed);
        // Existing templates predate the mandatory STT and unit columns.
        if (! in_array('stt', $columns, true)) array_unshift($columns, 'stt');
        if (! in_array('unit', $columns, true)) {
            $nameIndex = array_search('name', $columns, true);
            array_splice($columns, $nameIndex === false ? 2 : $nameIndex + 1, 0, ['unit']);
        }
        abort_if($columns === [], 422, 'Mẫu Excel không có cột hợp lệ.');
        return app(\Modules\ClientPortal\Applications\Pharma\Services\InventoryConfiguredExcelExporter::class)->download(
            $rows, $columns, array_replace($allowed, $profile?->headers ?? []),
            $profile?->widths ?? [], $profile?->alignments ?? [],
            array_replace(['title' => $report === 'stock' ? 'BÁO CÁO TỒN KHO' : 'BÁO CÁO XUẤT NHẬP TỒN'], $profile?->settings ?? []),
            $filename
        );
    }

    public function opening(Request $request, ApplicationRegistry $registry): View
    {
        $this->authorizeInventory($request, $registry, true);
        $medicines = Medicine::query()->orderBy('name')->get(['id','name','medicine_code']);
        $today = now()->toDateString();
        $supplierCosts = DB::table('pharma_supplier_trackings')
            ->select('medicine_id', DB::raw('AVG(COALESCE(NULLIF(cost_price, 0), NULLIF(import_price, 0))) as price'))
            ->where('status', 'active')
            ->whereRaw('COALESCE(NULLIF(cost_price, 0), NULLIF(import_price, 0)) > 0')
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->groupBy('medicine_id')->pluck('price', 'medicine_id');
        $warehouse = app(InventoryService::class)->defaultWarehouse();
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
        ]);
        $search = trim($filters['search'] ?? '');
        $openingLots = InventoryBalance::query()
            ->with('medicine:id,medicine_code,name')
            ->where('warehouse_id', $warehouse->id)
            ->where('opening_quantity', '>', 0)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('batch_number', 'like', '%'.$search.'%')
                        ->orWhereHas('medicine', fn ($medicine) => $medicine
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('medicine_code', 'like', '%'.$search.'%'));
                });
            })
            ->orderBy('medicine_id')->orderBy('batch_number')->orderBy('id')
            ->paginate(25)->withQueryString();
        return view('ClientPortal::applications.pharma.inventory-opening', compact('medicines', 'supplierCosts', 'openingLots', 'search'));
    }

    public function exportOpening(Request $request, ApplicationRegistry $registry, InventoryService $inventory): StreamedResponse
    {
        $this->authorizeInventory($request, $registry, true);
        $warehouse = $inventory->defaultWarehouse();
        // Use exactly the importer headers and ISO date format; never export current stock as opening stock.
        $rows = InventoryBalance::query()
            ->with('medicine:id,medicine_code')
            ->where('warehouse_id', $warehouse->id)
            ->where('opening_quantity', '>', 0)
            ->orderBy('medicine_id')->orderBy('batch_number')->orderBy('id')
            ->get()
            ->map(fn ($lot) => [
                'Ma thuoc' => $lot->medicine->medicine_code,
                'So lo' => $lot->batch_number,
                'Han dung' => $lot->expiry_date->format('Y-m-d'),
                'Ton dau ky' => (float) $lot->opening_quantity,
            ]);
        if ($rows->isEmpty()) {
            $rows = collect([['Ma thuoc' => '', 'So lo' => '', 'Han dung' => '', 'Ton dau ky' => '']]);
        }
        return (new FastExcel($rows))->download('client-pharma-ton-dau-ky-'.now()->format('Ymd-His').'.xlsx');
    }

    public function resetOpening(Request $request, ApplicationRegistry $registry, \Modules\Pharma\Services\OpeningInventoryResetService $reset): RedirectResponse
    {
        $this->authorizeInventory($request, $registry, true);
        $data = $request->validate([
            'confirmation' => 'required|string|in:XOA TON DAU KY',
            'backup_confirmed' => 'required|accepted',
        ]);
        $count = $reset->reset();
        \Illuminate\Support\Facades\Log::warning('Pharma opening inventory reset completed', [
            'actor_id' => $request->user('web')?->id, 'lots_deleted' => $count,
            'timestamp' => now()->toIso8601String(),
        ]);
        return redirect()->route('client.pharma.inventory.opening')
            ->with('success', 'Đã xóa '.$count.' lô tồn đầu kỳ. Có thể Import Excel trở lại.');
    }

    public function updateOpening(Request $request, ApplicationRegistry $registry, InventoryService $inventory, \Modules\Pharma\Services\OpeningLotEditor $editor, int $balance): RedirectResponse
    {
        $this->authorizeInventory($request, $registry, true);
        $data = $request->validate([
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'required|date',
            'opening_quantity' => 'required|numeric|gt:0|decimal:0,3',
            'manual_cost_price' => 'nullable|numeric|gt:0',
        ]);
        $warehouse = $inventory->defaultWarehouse();
        $editor->update((int) $warehouse->id, $balance, $data, (int) $request->user('web')->id);
        return redirect()->route('client.pharma.inventory.opening')->with('success', 'Đã cập nhật lô tồn đầu kỳ và sổ kho.');
    }

    public function storeOpeningManual(Request $request, ApplicationRegistry $registry, InventoryService $inventory): RedirectResponse
    {
        $this->authorizeInventory($request, $registry, true);
        $data = $request->validate([
            'medicine_id'=>'required|integer|exists:pharma_medicines,id',
            'batch_number'=>'required|string|max:100',
            'expiry_date'=>'required|date',
            'opening_quantity'=>'required|numeric|gt:0',
            'manual_cost_price'=>'nullable|numeric|gt:0',
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
            if (isset($data['manual_cost_price'])) {
                InventoryBalance::query()->where('warehouse_id', $warehouse->id)
                    ->where('medicine_id', $data['medicine_id'])->where('batch_number', $data['batch_number'])
                    ->whereDate('expiry_date', $data['expiry_date'])
                    ->update(['manual_cost_price' => round((float) $data['manual_cost_price'], 2)]);
            }
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
