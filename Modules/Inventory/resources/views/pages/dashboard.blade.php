@extends('Admin::layouts.master')

@section('title', 'Inventory Dashboard')

@section('content')
<div class="space-y-7">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Inventory operations center</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Quản lý kho</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Theo dõi tồn kho, chứng từ vận hành, lô/HSD và mở đúng workspace để xử lý.</p>
            <p class="mt-2 text-xs text-slate-500">Cập nhật {{ $dashboard['generated_at']->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.inventory.invoice-inbox') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-indigo-50 px-5 py-2.5 text-sm font-semibold text-indigo-800 transition hover:bg-indigo-100">Hóa đơn chờ nhập kho</a>
            <a href="{{ route('admin.inventory.receipts') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Mở phiếu nhập</a>
        </div>
    </header>

    @foreach($dashboard['warnings'] as $warning)
        <div role="alert" class="rounded-2xl border px-5 py-4 text-sm {{ $warning['level'] === 'danger' ? 'border-red-300 bg-red-50 text-red-900' : 'border-amber-300 bg-amber-50 text-amber-900' }}">{{ $warning['message'] }}</div>
    @endforeach

    <section>
        <h2 class="text-lg font-semibold text-slate-900">Tổng quan vận hành</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Kho đang hoạt động', 'warehouses', 'warehouses'], ['Mặt hàng', 'items', 'items'], ['Phiếu nhập nháp', 'draft_receipts', 'receipts'],
                ['Hóa đơn chờ nhập kho', 'invoice_inbox_pending', 'invoice-inbox'], ['Tồn thấp', 'low_stock', 'stock'], ['Lô sắp hết HSD', 'expiring_lots', 'lots'],
                ['Kiểm kê chờ xử lý', 'pending_stocktakes', 'stocktakes'], ['Biến động hôm nay', 'movements_today', 'movements'],
            ] as [$label, $metric, $route])
                <a href="{{ route('admin.inventory.'.$route) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                    <p class="text-sm font-medium text-slate-600">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-700">{{ number_format($dashboard['metrics'][$metric]) }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-slate-900">Workspaces</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach(['invoice-inbox' => 'Hóa đơn chờ nhập', 'warehouses' => 'Kho', 'items' => 'Mặt hàng', 'receipts' => 'Nhập kho', 'issues' => 'Xuất kho', 'transfers' => 'Điều chuyển', 'stocktakes' => 'Kiểm kê', 'stock' => 'Tồn kho', 'lots' => 'Lô / HSD', 'movements' => 'Biến động'] as $route => $label)
                <a href="{{ route('admin.inventory.'.$route) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-300"><p class="font-bold text-slate-950">{{ $label }}</p><p class="mt-1 text-xs text-slate-500">Mở workspace →</p></a>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Biến động gần đây</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Thời gian</th><th class="px-5 py-3">Kho</th><th class="px-5 py-3">Mặt hàng</th><th class="px-5 py-3">Loại</th><th class="px-5 py-3 text-right">Số lượng</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($dashboard['recent_movements'] as $movement)<tr><td class="px-5 py-3 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($movement->occurred_at)->format('d/m/Y H:i') }}</td><td class="px-5 py-3">{{ $movement->warehouse_name }}</td><td class="px-5 py-3"><span class="font-medium">{{ $movement->sku }}</span><br><span class="text-xs text-slate-500">{{ $movement->display_name }}</span></td><td class="px-5 py-3">{{ $movement->movement_type }}</td><td class="px-5 py-3 text-right font-semibold">{{ $movement->quantity_delta }} {{ $movement->base_uom }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Chưa có biến động kho.</td></tr>@endforelse
            </tbody></table>
        </div>
    </section>
</div>
@endsection
