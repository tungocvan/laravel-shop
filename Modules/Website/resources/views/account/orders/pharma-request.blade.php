@extends('Website::layouts.account')

@section('content-account')
<div class="space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('account.orders', ['tab' => 'pharma']) }}" class="text-sm font-medium text-emerald-700 hover:underline">← Đơn hàng của tôi</a>
            <h1 class="mt-2 text-xl font-bold tracking-tight text-slate-900">Yêu cầu #{{ str_pad($pharmaRequest->id, 4, '0', STR_PAD_LEFT) }}</h1>
            <p class="mt-1 text-xs text-slate-500">Ngày gửi: {{ $pharmaRequest->submitted_at?->format('d/m/Y H:i') }}</p>
        </div>
        <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $pharmaRequest->customer_order_status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($pharmaRequest->customer_order_status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800') }}">{{ ['pending_review' => 'Chờ xét duyệt', 'processing' => 'Đang xử lý', 'completed' => 'Hoàn tất · Đã ghi sổ', 'rejected' => 'Đã từ chối'][$pharmaRequest->customer_order_status] }}</span>
    </header>

    <section class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-base font-bold text-slate-900">Tiến độ xử lý</h2>
        <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs sm:gap-3">
            <div><div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 font-bold text-white">✓</div><p class="mt-2 font-semibold text-slate-800">Đã gửi yêu cầu</p></div>
            <div><div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full {{ $pharmaRequest->status === 'pending_review' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-700 text-white' }} font-bold">{{ $pharmaRequest->status === 'pending_review' ? '2' : '✓' }}</div><p class="mt-2 font-semibold text-slate-800">{{ $pharmaRequest->status === 'pending_review' ? 'Đang xét duyệt' : 'Đã xét duyệt' }}</p></div>
            <div><div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full {{ $pharmaRequest->status === 'accepted' ? 'bg-emerald-100 text-emerald-700' : ($pharmaRequest->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-400') }} font-bold">{{ $pharmaRequest->status === 'accepted' ? '✓' : ($pharmaRequest->status === 'rejected' ? '!' : '3') }}</div><p class="mt-2 font-semibold text-slate-800">{{ $pharmaRequest->customer_order_status === 'completed' ? 'Đã ghi sổ' : ($pharmaRequest->customer_order_status === 'processing' ? 'Đang xử lý' : ($pharmaRequest->status === 'rejected' ? 'Không được chấp nhận' : 'Chờ kết quả')) }}</p></div>
        </div>
        @if($pharmaRequest->customer_order_status === 'completed')
            <p class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800">Đã ghi sổ xuất kho thành công{{ $pharmaRequest->inventoryIssue?->posted_at ? ' lúc '.$pharmaRequest->inventoryIssue->posted_at->format('d/m/Y H:i') : '' }}. Trạng thái này không xác nhận đã giao hàng.</p>
        @elseif($pharmaRequest->customer_order_status === 'processing')
            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">Yêu cầu đã được tiếp nhận và đang xử lý xuất kho. Chưa ghi sổ hoàn tất.</p>
        @endif
        <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600">Việc chấp nhận yêu cầu chỉ xác nhận đủ điều kiện tiếp nhận, không đồng nghĩa đã xuất kho hoặc giao hàng.</p>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-4 sm:px-5">
            <h2 class="text-base font-bold text-slate-900">Sản phẩm đã yêu cầu</h2>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ count($lines) }} sản phẩm</span>
        </div>
        <div class="divide-y divide-slate-100 px-4 sm:px-5">
            @foreach($lines as $line)
                <div class="flex items-center gap-3 py-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">Thuốc</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold leading-5 text-slate-900">{{ $line['title'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Mã sản phẩm #{{ $line['product_id'] }}</p>
                    </div>
                    <span class="shrink-0 rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-bold tabular-nums text-emerald-800">× {{ $line['quantity'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="border-t border-slate-100 bg-amber-50/60 px-4 py-3 text-xs leading-5 text-amber-800 sm:px-5">Đơn giá (Có VAT), tổng tiền và điều kiện cung ứng sẽ được xác minh khi xử lý yêu cầu.</div>
    </section>

    <section class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="border-b border-slate-100 pb-3 text-base font-bold text-slate-900">Thông tin người mua và nhận hàng</h2>
        <dl class="mt-4 grid gap-x-5 gap-y-4 text-sm sm:grid-cols-2">
            <div><dt class="text-xs text-slate-500">Loại người mua</dt><dd class="mt-1 font-medium text-slate-900">{{ $pharmaRequest->buyer_type === 'company' ? 'Doanh nghiệp / cơ sở' : 'Cá nhân' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Doanh nghiệp / cơ sở</dt><dd class="mt-1 font-medium text-slate-900">{{ $pharmaRequest->company_name ?: 'Chưa cung cấp' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Mã số thuế</dt><dd class="mt-1 font-medium text-slate-900">{{ $pharmaRequest->tax_code ?: 'Chưa cung cấp' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Người nhận</dt><dd class="mt-1 font-medium text-slate-900">{{ $pharmaRequest->recipient_name ?: 'Chưa cung cấp' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Số điện thoại</dt><dd class="mt-1 font-medium text-slate-900">{{ $pharmaRequest->phone ?: 'Chưa cung cấp' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Địa chỉ nhận hàng</dt><dd class="mt-1 whitespace-pre-wrap font-medium text-slate-900">{{ $pharmaRequest->shipping_address ?: 'Chưa cung cấp' }}</dd></div>
            @if($pharmaRequest->notes)<div class="sm:col-span-2"><dt class="text-xs text-slate-500">Ghi chú</dt><dd class="mt-1 whitespace-pre-wrap text-slate-700">{{ $pharmaRequest->notes }}</dd></div>@endif
        </dl>
    </section>
    @if($pharmaRequest->status === 'rejected' && $pharmaRequest->review_note)
        <section class="rounded-xl border border-rose-100 bg-rose-50 p-4 text-sm text-rose-800"><h2 class="font-semibold">Lý do từ chối</h2><p class="mt-2 whitespace-pre-wrap">{{ $pharmaRequest->review_note }}</p></section>
    @endif
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('account.orders', ['tab' => 'pharma']) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">← Danh sách yêu cầu</a>
        <a href="{{ route('website.pharma.quick-order') }}" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Đặt hàng nhanh mới</a>
    </div>
</div>
@endsection
