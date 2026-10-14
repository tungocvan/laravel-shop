@extends('Website::layouts.account')

@section('content-account')
<div class="space-y-5">
    <a href="{{ route('account.orders', ['tab' => 'pharma']) }}" class="text-sm font-medium text-emerald-700">← Yêu cầu Pharma của tôi</a>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-xl font-bold">Yêu cầu #{{ $pharmaRequest->id }}</h1><p class="text-sm text-slate-500">{{ $pharmaRequest->submitted_at?->format('d/m/Y H:i') }}</p></div>
        <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-semibold">{{ ['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối'][$pharmaRequest->status] ?? $pharmaRequest->status }}</span>
    </div>
    <section class="rounded-xl border bg-white p-4">
        <h2 class="font-semibold">Sản phẩm đã yêu cầu</h2>
        <div class="mt-3 divide-y">
            @foreach($lines as $line)
                <div class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0"><p class="font-medium">{{ $line['title'] }}</p><p class="text-xs text-slate-500">Mã #{{ $line['product_id'] }}</p></div>
                    <span class="shrink-0 font-semibold">× {{ $line['quantity'] }}</span>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-amber-700">Giá và điều kiện cung ứng sẽ được xác minh khi xử lý yêu cầu.</p>
    </section>
    <section class="rounded-xl border bg-white p-4">
        <h2 class="font-semibold">Thông tin nhận hàng đã gửi</h2>
        <dl class="mt-3 grid gap-2 text-sm">
            <div><dt class="text-slate-500">Doanh nghiệp / cơ sở</dt><dd>{{ $pharmaRequest->company_name ?: 'Chưa cung cấp' }}</dd></div>
            <div><dt class="text-slate-500">Người nhận</dt><dd>{{ $pharmaRequest->recipient_name ?: 'Chưa cung cấp' }}</dd></div>
            <div><dt class="text-slate-500">Điện thoại</dt><dd>{{ $pharmaRequest->phone ?: 'Chưa cung cấp' }}</dd></div>
            <div><dt class="text-slate-500">Địa chỉ</dt><dd class="whitespace-pre-wrap">{{ $pharmaRequest->shipping_address ?: 'Chưa cung cấp' }}</dd></div>
        </dl>
    </section>
    @if($pharmaRequest->status === 'rejected' && $pharmaRequest->review_note)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm"><strong>Lý do từ chối:</strong><p class="mt-2 whitespace-pre-wrap">{{ $pharmaRequest->review_note }}</p></div>
    @endif
</div>
@endsection
