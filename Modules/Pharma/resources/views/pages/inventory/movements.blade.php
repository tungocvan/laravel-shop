@extends('Admin::layouts.master')
@section('title','Xuất – Nhập – Tồn')

@section('content')
<div class="space-y-5">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Tồn kho</a>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma Inventory</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950">Xuất – Nhập – Tồn</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $warehouse->name }} · Mốc bắt đầu sổ kho: <strong>{{ $warehouse->opening_cutoff_at?->format('d/m/Y H:i') ?? 'Chưa ghi nhận' }}</strong></p>
        </div>
        <nav class="flex flex-wrap gap-2" aria-label="Điều hướng kho">
            <a href="{{ route('admin.pharma.inventory.index') }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Tồn kho</a>
            <span class="rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Xuất–Nhập–Tồn</span>
            <a href="{{ route('admin.pharma.inventory.receipts.index') }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Phiếu nhập</a>
            <a href="{{ route('admin.pharma.inventory.issues.index') }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700">Phiếu xuất</a>
        </nav>
    </header>

    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ $errors->first() }}</div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 lg:grid-cols-[auto_auto_minmax(280px,1fr)_auto_auto] lg:items-end">
            <label class="text-xs font-semibold text-slate-600">Từ ngày<input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Đến ngày<input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Tên thuốc
                <x-select-search id="inventory-movement-medicine" name="movement_medicine_id" placeholder="Tất cả thuốc">
                    <option value="">Tất cả thuốc</option>
                    @foreach($movementMedicines as $medicine)<option value="{{ $medicine->id }}" @selected((int)$movementMedicineId===$medicine->id)>{{ $medicine->medicine_code }} · {{ $medicine->name }}</option>@endforeach
                </x-select-search>
            </label>
            <button class="min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white">Lọc dữ liệu</button>
            <a href="{{ route('admin.pharma.inventory.movements.index') }}" class="flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700">Xóa bộ lọc</a>
        </form>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Giá trị tồn đầu kỳ',$movement['opening_value']],
            ['Giá trị nhập trong kỳ',$movement['in_value']],
            ['Giá trị xuất trong kỳ',$movement['out_value']],
            ['Giá trị tồn cuối kỳ',$movement['closing_value']],
        ] as [$label,$value])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-extrabold text-slate-950">{{ number_format((float)$value,0,',','.') }} đ</p></div>
        @endforeach
    </section>
    @if($movement['unpriced_count'] > 0)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-800">
            Có {{ number_format($movement['unpriced_count']) }} lô chưa định giá hoặc giá vốn 0 đ; các lô này đóng góp 0 đ vào KPI giá trị. Giá trị X-N-T hiện dùng giá vốn hiệu lực theo lô tại thời điểm xem báo cáo.
        </div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-slate-950">Chi tiết theo lô</h2>
                <p id="movement-selection-summary" class="mt-0.5 text-xs text-slate-500">Chưa chọn dòng nào</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.pharma.inventory.movements.export',['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d'),'movement_medicine_id'=>$movementMedicineId]) }}" class="rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">Export theo bộ lọc</a>
                <button type="submit" form="movement-selected-export" id="movement-export-selected" disabled class="rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Export đã chọn</button>
            </div>
        </div>
        <form id="movement-selected-export" method="GET" action="{{ route('admin.pharma.inventory.movements.export') }}">
            <input type="hidden" name="from" value="{{ $from->format('Y-m-d') }}"><input type="hidden" name="to" value="{{ $to->format('Y-m-d') }}">
            @if($movementMedicineId)<input type="hidden" name="movement_medicine_id" value="{{ $movementMedicineId }}">@endif
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr>
                    <th class="w-10 px-3 py-3 text-center"><input id="movement-select-all" type="checkbox" class="h-4 w-4 rounded border-slate-300" aria-label="Chọn tất cả dòng đang hiển thị"></th>
                    <th class="px-3 py-3">Mã thuốc</th><th class="px-3 py-3">Thuốc</th><th class="px-3 py-3">Lô / HSD</th>
                    <th class="px-3 py-3 text-right">Tồn đầu</th><th class="px-3 py-3 text-right">Nhập</th><th class="px-3 py-3 text-right">Xuất</th><th class="px-3 py-3 text-right">Tồn cuối</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($movement['rows'] as $row)
                    <tr>
                        <td class="px-3 py-3 text-center"><input form="movement-selected-export" data-movement-check type="checkbox" name="ids[]" value="{{ $row->id }}" class="h-4 w-4 rounded border-slate-300"></td>
                        <td class="px-3 py-3 font-mono text-xs font-semibold text-indigo-700">{{ $row->medicine_code }}</td>
                        <td class="px-3 py-3 font-semibold">{{ $row->name }}</td>
                        <td class="px-3 py-3">{{ $row->batch_number }}<div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') }}</div></td>
                        <td class="px-3 py-3 text-right">{{ number_format((float)$row->period_opening,0,',','.') }}</td>
                        <td class="px-3 py-3 text-right text-emerald-700">{{ number_format((float)$row->period_in,0,',','.') }}</td>
                        <td class="px-3 py-3 text-right text-amber-700">{{ number_format((float)$row->period_out,0,',','.') }}</td>
                        <td class="px-3 py-3 text-right font-bold">{{ number_format((float)$row->period_closing,0,',','.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Chưa có dữ liệu kho trong khoảng thời gian đã chọn.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const all=document.getElementById('movement-select-all'), boxes=[...document.querySelectorAll('[data-movement-check]')];
    const button=document.getElementById('movement-export-selected'), summary=document.getElementById('movement-selection-summary');
    const sync=()=>{ const count=boxes.filter(box=>box.checked).length; if(all){ all.checked=boxes.length>0&&count===boxes.length; all.indeterminate=count>0&&count<boxes.length; } if(button) button.disabled=count===0; if(summary) summary.textContent=count ? 'Đã chọn '+count+' dòng' : 'Chưa chọn dòng nào'; };
    all?.addEventListener('change',()=>{ boxes.forEach(box=>box.checked=all.checked); sync(); });
    boxes.forEach(box=>box.addEventListener('change',sync)); sync();
});
</script>
@endsection
