@extends('Website::layouts.account')

@section('content-account')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-slate-900">Đơn hàng của tôi</h1>
        <a href="{{ route('website.pharma.quick-order') }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">Đặt hàng nhanh Pharma</a>
    </div>
    <div class="flex flex-wrap gap-2 border-b pb-3">
        <a href="{{ route('account.orders', ['tab' => 'pharma']) }}" class="rounded-lg px-3 py-2 text-sm {{ request('tab', 'pharma') === 'pharma' ? 'bg-emerald-700 font-semibold text-white' : 'bg-slate-100 text-slate-700' }}">Yêu cầu Pharma ({{ $pharmaRequests->total() }})</a>
        <a href="{{ route('account.orders', ['tab' => 'regular']) }}" class="rounded-lg px-3 py-2 text-sm {{ request('tab') === 'regular' ? 'bg-emerald-700 font-semibold text-white' : 'bg-slate-100 text-slate-700' }}">Đơn hàng thông thường</a>
    </div>
    @if(request('tab', 'pharma') === 'pharma')
        <p class="text-sm text-slate-500">Theo dõi yêu cầu mua dược phẩm. Chấp nhận yêu cầu chưa đồng nghĩa với xuất kho.</p>
        <div class="divide-y overflow-hidden rounded-xl border bg-white">
            @forelse($pharmaRequests as $item)
                <a href="{{ route('account.pharma-requests.show', $item->id) }}" class="flex flex-wrap items-center justify-between gap-3 p-4 transition hover:bg-slate-50">
                    <div class="space-y-1">
                        <div class="font-semibold text-slate-900">Yêu cầu #{{ $item->id }}</div>
                        <div class="text-xs text-slate-500">{{ $item->submitted_at?->format('d/m/Y H:i') }} · {{ count($item->requested_items ?? []) }} sản phẩm</div>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $item->status === 'accepted' ? 'bg-green-50 text-green-700' : ($item->status === 'rejected' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800') }}">{{ ['pending_review' => 'Chờ xét duyệt', 'accepted' => 'Đã chấp nhận', 'rejected' => 'Đã từ chối'][$item->status] ?? $item->status }}</span>
                </a>
            @empty
                <div class="p-8 text-center text-sm text-slate-500">Bạn chưa gửi yêu cầu đặt hàng Pharma nào.</div>
            @endforelse
        </div>
        <div>{{ $pharmaRequests->links() }}</div>
    @else
        @livewire('website.account.order-list')
    @endif
</div>
@endsection
