@extends('ClientPortal::layouts.application')

@section('title', $issue->number)
@section('app-name', $application['name'])
@section('app-subtitle', 'Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-mobile-navigation', true)
@section('hide-application-header', true)

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.').' đ';
    $statusLabels = ['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','posted'=>'Đã xuất','cancelled'=>'Đã hủy'];
    $source = ($issue->issue_source ?? 'normal') === 'bid' ? 'Theo kết quả trúng thầu' : 'Theo bảng giá';
    $deferredAllocationIds = $savedSupplyNotes->keys()->map(fn($id) => (int) $id);
    $fulfilledItems = $issue->items->filter(function ($item) use ($deferredAllocationIds) {
        return ! $deferredAllocationIds->contains((int) $item->drug_bid_award_allocation_id);
    });
    $deferredItems = $issue->items->filter(function ($item) use ($deferredAllocationIds) {
        return $deferredAllocationIds->contains((int) $item->drug_bid_award_allocation_id);
    });
    $displayItems = $issue->status === 'approved' ? $fulfilledItems : $issue->items;
    $total = $displayItems->sum(fn($item)=>(float)$item->quantity*(float)$item->unit_price);
@endphp
<div class="min-h-screen bg-slate-50 pb-8">
    <header class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:rounded-3xl lg:border lg:px-6">
        <div class="relative flex items-center justify-center">
            <a href="{{ route('client.pharma.orders') }}" class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full text-2xl text-slate-900 active:scale-95" aria-label="Quay lại">←</a>
            <h1 class="px-12 text-center text-xl font-black text-slate-950">Chi tiết đơn hàng</h1>
        </div>
    </header>

    <main class="mx-auto mt-3 max-w-4xl space-y-3">
        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div><p class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $issue->number }}</p><h2 class="mt-1 text-xl font-black text-slate-950">{{ $issue->recipient_name ?: 'Chưa xác định nơi nhận' }}</h2></div>
                <span class="rounded-full px-3 py-1 text-xs font-black {{ $issue->status==='posted' ? 'bg-emerald-100 text-emerald-800' : ($issue->status==='cancelled' ? 'bg-slate-200 text-slate-600' : 'bg-amber-100 text-amber-800') }}">{{ $statusLabels[$issue->status] ?? $issue->status }}</span>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-slate-100 pt-3 text-sm">
                <div><dt class="text-slate-500">Nguồn đơn hàng</dt><dd class="mt-1 font-black text-slate-900">{{ $source }}</dd></div>
                <div><dt class="text-slate-500">Ngày lập</dt><dd class="mt-1 font-black text-slate-900">{{ $issue->issue_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-slate-500">Người phụ trách</dt><dd class="mt-1 font-black text-slate-900">{{ $issue->manager?->name ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Bảng giá</dt><dd class="mt-1 font-black text-slate-900">{{ $issue->priceList?->name ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-end justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-wide text-slate-500">Sản phẩm</p><h2 class="mt-1 text-lg font-black text-slate-950">{{ number_format($displayItems->count()) }} sản phẩm</h2></div><p class="text-right text-lg font-black text-slate-950">{{ $money($total) }}</p></div>
            <div class="mt-4 divide-y divide-slate-100">
                @foreach($displayItems as $item)
                    <article class="py-4 first:pt-0 last:pb-0">
                        <h3 class="font-black text-slate-950">{{ $item->medicine?->name ?: 'Sản phẩm #'.$item->medicine_id }}</h3>
                        <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                            <div><p class="text-xs text-slate-500">Số lượng</p><p class="font-bold text-slate-800">{{ rtrim(rtrim(number_format((float)$item->quantity,3,'.',''),'0'),'.') }}</p></div>
                            <div class="text-right"><p class="text-xs text-slate-500">Đơn giá</p><p class="font-bold text-slate-800">{{ $money($item->unit_price) }}</p></div>
                        </div>
                        @if($item->batch_number || $item->expiry_date)
                            <p class="mt-2 text-xs text-slate-500">Lô {{ $item->batch_number ?: '—' }} · HSD {{ $item->expiry_date?->format('d/m/Y') ?: '—' }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        @if($issue->status === 'approved' && $deferredItems->isNotEmpty())
            <details class="rounded-3xl border border-amber-200 bg-amber-50/50 shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-5">
                    <div><p class="text-xs font-black uppercase tracking-wide text-amber-800">Chờ cung cấp</p><p class="mt-1 text-sm font-black text-slate-900">{{ $deferredItems->count() }} sản phẩm chưa có hàng</p></div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-900">Không tính là hàng đã duyệt xuất</span>
                </summary>
                <div class="space-y-3 px-5 pb-5">
                    @foreach($deferredItems as $item)
                        @php($supply = $savedSupplyNotes->get($item->drug_bid_award_allocation_id))
                        <article class="rounded-2xl border border-amber-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3"><p class="font-black text-slate-900">{{ $item->medicine?->name ?: 'Sản phẩm #'.$item->medicine_id }}</p><p class="text-xs font-black text-amber-800">SL {{ rtrim(rtrim(number_format((float)$item->quantity,3,'.',''),'0'),'.') }}</p></div>
                            <p class="mt-2 text-xs text-slate-600">Dự kiến cung cấp: <span class="font-black text-slate-800">{{ $supply?->expected_supply_date?->format('d/m/Y') ?: '—' }}</span></p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">{{ $supply?->note }}</p>
                        </article>
                    @endforeach
                </div>
            </details>
        @endif

        @if($canApproveOrder && $stockReadiness)
            <details class="rounded-3xl border {{ $stockReadiness['is_ready'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-amber-200 bg-amber-50/50' }} shadow-sm" open>
                <summary class="flex cursor-pointer list-none items-start justify-between gap-3 p-5">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Kiểm tra khả năng xuất kho</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">Tồn kho hiện tại · chạm để ẩn/hiện</p>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black {{ $stockReadiness['is_ready'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}">{{ $stockReadiness['is_ready'] ? 'Đủ hàng' : 'Không đủ hàng' }}</span>
                </summary>
                <div class="px-5 pb-5">
                <div class="hidden">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Kiểm tra khả năng xuất kho</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">Tồn kho hiện tại · chỉ đọc</p>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-black {{ $stockReadiness['is_ready'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}">
                        {{ $stockReadiness['is_ready'] ? 'Đủ hàng' : 'Không đủ hàng' }}
                    </span>
                </div>
                <div class="mt-0 space-y-3">
                    @foreach($stockReadiness['rows'] as $stockRow)
                        <article class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-black text-slate-900">{{ $stockRow['medicine_name'] }}</p>
                                    @if($stockRow['medicine_code'])<p class="mt-0.5 text-xs text-slate-500">{{ $stockRow['medicine_code'] }}</p>@endif
                                </div>
                                <span class="shrink-0 text-xs font-black {{ $stockRow['is_ready'] ? 'text-emerald-700' : 'text-amber-800' }}">{{ $stockRow['is_ready'] ? 'Đủ hàng' : 'Thiếu '.number_format($stockRow['shortage_quantity'],3,'.','') }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                                <div><p class="text-xs text-slate-500">SL đơn hàng</p><p class="font-black text-slate-900">{{ number_format($stockRow['requested_quantity'],3,'.','') }}</p></div>
                                <div><p class="text-xs text-slate-500">Tồn khả dụng</p><p class="font-black text-slate-900">{{ number_format($stockRow['available_stock'],3,'.','') }}</p></div>
                            </div>
                            @unless($stockRow['is_ready'])
                                <form method="POST" action="{{ route('client.pharma.orders.supply-notes', $issue) }}" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3">
                                    @csrf
                                    <p class="text-xs font-black text-amber-900">Hiện kho đang hết/thiếu hàng</p>
                                    <p class="mt-1 text-xs leading-5 text-amber-800">Ghi chú ngày dự kiến và tình trạng cung cấp để theo dõi mặt hàng đang thiếu.</p>
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        <label class="block"><span class="mb-1 block text-[11px] font-bold text-slate-600">Dự kiến cung cấp lại</span><input type="date" name="supply_notes[{{ $stockRow['item_id'] }}][expected_supply_date]" value="{{ old('supply_notes.'.$stockRow['item_id'].'.expected_supply_date', $stockRow['supply_expected_date'] ?: now()->addMonth()->format('Y-m-d')) }}" class="h-10 w-full rounded-lg border border-amber-200 bg-white px-2 text-sm"></label>
                                        <label class="block"><span class="mb-1 block text-[11px] font-bold text-slate-600">Ghi chú *</span><input type="text" required maxlength="2000" name="supply_notes[{{ $stockRow['item_id'] }}][note]" value="{{ old('supply_notes.'.$stockRow['item_id'].'.note', $stockRow['supply_note'] ?: 'Hiện kho đang hết hàng. Đơn hàng dự kiến cung cấp lại.') }}" class="h-10 w-full rounded-lg border border-amber-200 bg-white px-2 text-sm"></label>
                                    </div>
                                    <button class="mt-2 h-9 w-full rounded-lg border border-amber-300 bg-white text-xs font-black text-amber-900">{{ $stockRow['has_supply_note'] ? 'Cập nhật ghi chú' : 'Lưu ghi chú' }}</button>
                                </form>
                            @endunless
                            <details class="mt-3 rounded-xl bg-slate-50 px-3 py-2">
                                <summary class="cursor-pointer text-xs font-black text-slate-700">Lô khả dụng · {{ count($stockRow['lots']) }}</summary>
                                <div class="mt-2 space-y-2">
                                    @forelse($stockRow['lots'] as $lot)
                                        <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2 text-xs">
                                            <div><span class="font-black text-slate-800">{{ $lot['batch_number'] }}</span><span class="ml-2 text-slate-500">HSD {{ $lot['expiry_date'] }}</span></div>
                                            <span class="font-black text-slate-900">Còn {{ number_format($lot['quantity_on_hand'],3,'.','') }}</span>
                                        </div>
                                    @empty
                                        <p class="text-xs font-bold text-amber-800">Không có lô còn tồn và còn hạn sử dụng.</p>
                                    @endforelse
                                </div>
                            </details>
                        </article>
                    @endforeach
                </div>
                <p class="mt-3 text-xs leading-5 text-slate-500">Thông tin tồn kho này chỉ để tham khảo khi phê duyệt và không giữ hàng. Thiếu tồn không chặn phê duyệt đơn; mặt hàng thiếu tiếp tục theo dõi chờ cung ứng. Chọn lô thực xuất và kiểm tra đủ tồn ở bước xử lý kho/Ghi sổ.</p>
                </div>
            </details>
        @endif

        @if($issue->notes)
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Ghi chú</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $issue->notes }}</p></section>
        @endif
        @if($issue->status === 'rejected' && $issue->rejection_reason)
            <section class="rounded-3xl border border-rose-200 bg-rose-50 p-5"><p class="text-xs font-black uppercase tracking-wide text-rose-700">Lý do từ chối</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-rose-900">{{ $issue->rejection_reason }}</p></section>
        @endif
    </main>
    @if($canEditOrder || $canSubmitOrder || $canApproveOrder || ($canUndoApproval ?? false) || ($canDeleteOrder ?? false) || ($canPostOrder ?? false))
        <div class="mx-auto mt-3 max-w-4xl rounded-2xl border border-slate-200 bg-white px-3 py-2.5 shadow-sm">
            @if($canDeleteOrder ?? false)
                <form method="POST" action="{{ route('client.pharma.orders.delete',$issue) }}" onsubmit="return confirm('Xóa đơn {{ $issue->number }}? Đơn chưa ghi sổ nên thao tác này không ảnh hưởng tồn kho.');">
                    @csrf @method('DELETE')
                    <button class="h-13 w-full rounded-2xl border border-rose-300 bg-white font-black text-rose-700">Xóa đơn</button>
                </form>
                <p class="mt-2 text-xs leading-5 text-slate-500">Chỉ xóa đơn Nháp/Từ chối chưa ghi sổ. Không tạo giao dịch kho và không thay đổi tồn.</p>
            @elseif($canPostOrder ?? false)
                <div class="flex items-center justify-end gap-2">
                    @if($canUndoApproval ?? false)
                        <form method="POST" action="{{ route('client.pharma.orders.undo-approval',$issue) }}">@csrf
                            <button class="h-10 rounded-xl border border-amber-300 bg-amber-50 px-4 text-sm font-black text-amber-900">Hoàn tác</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('client.pharma.orders.post',$issue) }}">@csrf
                        <button class="h-10 min-w-[108px] rounded-xl bg-emerald-700 px-4 text-sm font-black text-white disabled:cursor-not-allowed disabled:bg-slate-300"
                            @disabled(!($stockReadiness['can_post_directly'] ?? false))>Ghi sổ</button>
                    </form>
                </div>
                @if(!($stockReadiness['can_post_directly'] ?? false))
                    <p class="mt-2 text-xs leading-5 text-amber-800">Chưa có sản phẩm đủ điều kiện ghi sổ. Sản phẩm chờ cung ứng sẽ không bị trừ tồn.</p>
                @else
                    <p class="mt-2 text-xs leading-5 text-slate-500">Ghi sổ chỉ xuất các sản phẩm đủ tồn; sản phẩm chờ cung ứng được giữ lại, không trừ tồn và không tính doanh thu/hoa hồng.</p>
                @endif
            @elseif($canUndoApproval ?? false)
                <form method="POST" action="{{ route('client.pharma.orders.undo-approval',$issue) }}">@csrf
                    <button class="h-13 w-full rounded-2xl border border-amber-300 bg-amber-50 font-black text-amber-900">Hoàn tác phê duyệt</button>
                </form>
                <p class="mt-2 text-xs leading-5 text-slate-500">Đơn chưa ghi sổ kho nên có thể đưa về Chờ duyệt để kiểm tra lại tồn hàng.</p>
            @elseif($canApproveOrder)
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" id="order-reject-toggle" class="h-13 rounded-2xl border border-rose-300 bg-white font-black text-rose-700">Từ chối</button>
                    <form method="POST" action="{{ route('client.pharma.orders.approve',$issue) }}">@csrf<button  class="h-13 w-full rounded-2xl bg-slate-950 font-black text-white disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">Phê duyệt</button></form>
                </div>
                @if(!($stockReadiness['can_approve'] ?? false))
                    <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-xs font-bold leading-5 text-amber-900">Chưa thể phê duyệt: đơn phải có ít nhất 1 sản phẩm đủ tồn; mọi sản phẩm thiếu phải lưu đầy đủ ngày dự kiến cung cấp và ghi chú.</p>
                @endif
                <form id="order-reject-form" method="POST" action="{{ route('client.pharma.orders.reject',$issue) }}" class="mt-3 hidden rounded-2xl border border-rose-200 bg-rose-50 p-3">
                    @csrf
                    <label class="block"><span class="mb-2 block text-sm font-black text-rose-900">Lý do từ chối *</span><textarea name="rejection_reason" rows="3" required maxlength="1000" class="w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm" placeholder="Nhập lý do để User biết cần điều chỉnh gì...">{{ old('rejection_reason') }}</textarea></label>
                    <button class="mt-3 h-11 w-full rounded-xl bg-rose-700 font-black text-white">Xác nhận từ chối</button>
                </form>
            @else
                <div class="flex items-center justify-end gap-2">
                    @if($canEditOrder)<a href="{{ route('client.pharma.orders.edit',$issue) }}" class="inline-flex h-10 min-w-[108px] items-center justify-center rounded-xl border border-slate-300 px-4 text-sm font-black text-slate-700 active:scale-[0.985]">Sửa đơn</a>@endif
                    @if($canSubmitOrder)<form method="POST" action="{{ route('client.pharma.orders.submit',$issue) }}">@csrf<button class="h-10 min-w-[118px] rounded-xl bg-slate-950 px-4 text-sm font-black text-white active:scale-[0.985]">Gửi duyệt</button></form>@endif
                </div>
            @endif
        </div>
    @endif
    @if($canApproveOrder)
        <script>document.getElementById('order-reject-toggle')?.addEventListener('click',()=>document.getElementById('order-reject-form')?.classList.toggle('hidden'));</script>
    @endif
</div>
@endsection
